<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\Student\Student;
use App\Models\Student\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentAttendanceController extends Controller
{
    public function index()
    {
        $branchId = auth()->user()->branch_id;

        $branches = Branch::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('id', $branchId);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        $classnames = ClassName::select('id', 'name', 'class_numeral')->get();

        return view('students.attendance.index', compact('branches', 'classnames'));
    }

    public function getBatches($branchId)
    {
        try {
            $userBranchId = auth()->user()->branch_id;

            if ($userBranchId != 0 && $userBranchId != $branchId) {
                return response()->json(
                    [
                        'batches' => [],
                        'message' => 'Unauthorized access to this branch.',
                    ],
                    403,
                );
            }

            $batches = Batch::with('branch:id,branch_name')
                ->where('branch_id', $branchId)
                ->select('id', 'name', 'day_off', 'branch_id')
                ->get()
                ->map(function ($batch) {
                    return [
                        'id'          => $batch->id,
                        'name'        => $batch->name,
                        'day_off'     => $batch->day_off,
                        'branch_name' => $batch->branch->branch_name ?? '',
                    ];
                });

            return response()->json([
                'batches' => $batches,
                'count'   => $batches->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'batches' => [],
                    'message' => 'Error loading batches: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getStudents(Request $request)
    {
        $request->validate([
            'branch_id'       => 'required',
            'class_id'        => 'required',
            'batch_id'        => 'required',
            'attendance_date' => 'required',
        ]);

        $dateCarbon = Carbon::createFromFormat('d-m-Y', $request->attendance_date);
        $dbDate     = $dateCarbon->format('Y-m-d');
        $dayName    = $dateCarbon->format('l');

        $batch    = Batch::find($request->batch_id);
        $isOffDay = false;

        if ($batch && strcasecmp($batch->day_off, $dayName) === 0) {
            $isOffDay = true;
        }

        $students = Student::active()
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->where('batch_id', $request->batch_id)
            ->select('id', 'name', 'student_unique_id')
            ->with([
                'attendances' => function ($query) use ($dbDate) {
                    $query->whereDate('attendance_date', $dbDate);
                },
            ])
            ->get();

        $data = $students->map(function ($student) {
            $attendance = $student->attendances->first();
            return [
                'id'                => $student->id,
                'name'              => $student->name,
                'student_unique_id' => $student->student_unique_id,
                'status'            => $attendance ? $attendance->status : null,
                'remarks'           => $attendance ? $attendance->remarks : '',
            ];
        });

        return response()->json([
            'students'     => $data,
            'count'        => $data->count(),
            'is_off_day'   => $isOffDay,
            'off_day_name' => $dayName,
        ]);
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'attendance_date'          => 'required',
            'branch_id'                => 'required',
            'class_id'                 => 'required',
            'batch_id'                 => 'required',
            'attendances'              => 'required|array',
            'attendances.*.student_id' => 'required',
            'attendances.*.status'     => 'required|in:present,late,absent',
        ]);

        $date = Carbon::createFromFormat('d-m-Y', $request->attendance_date)->format('Y-m-d');

        DB::beginTransaction();
        try {
            foreach ($request->attendances as $att) {
                StudentAttendance::updateOrCreate(
                    [
                        'student_id'      => $att['student_id'],
                        'attendance_date' => $date,
                    ],
                    [
                        'branch_id'  => $request->branch_id,
                        'class_id'   => $request->class_id,
                        'batch_id'   => $request->batch_id,
                        'status'     => $att['status'],
                        'remarks'    => $att['remarks'] ?? null,
                        'created_by' => auth()->id(),
                    ],
                );
            }
            DB::commit();
            return response()->json(['message' => 'Attendance saved successfully!', 'status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error saving data: ' . $e->getMessage(), 'status' => 'error'], 500);
        }
    }
}
