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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = auth()->user()->branch_id;

        $branches = Branch::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('id', $branchId);
        })->select('id', 'branch_name', 'branch_prefix')->get();

        $classnames = ClassName::where('is_active', true)->select('id', 'name', 'class_numeral')->get();

        $batches = Batch::with('branch:id,branch_name')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name', 'day_off', 'branch_id')
            ->get();

        return view('students.attendance.index', compact('branches', 'classnames', 'batches'));
    }

    /**
     * AJAX: Fetch students based on filters and include today's attendance if exists
     */
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
        $dayName    = $dateCarbon->format('l'); // e.g., "Friday", "Monday"

        // 1. Check for Off Day
        $batch    = Batch::find($request->batch_id);
        $isOffDay = false;

        // Compare batch day_off (assuming it stores "Friday") with current day name
        // strcasecmp makes it case-insensitive
        if ($batch && strcasecmp($batch->day_off, $dayName) === 0) {
            $isOffDay = true;
        }

        // 2. Fetch Students
        $students = Student::where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->where('batch_id', $request->batch_id)
            ->whereHas('studentActivation', function ($q2) {
                        $q2->where('active_status', 'active');
                    })
            ->select('id', 'name', 'student_unique_id')
            ->with(['attendances' => function ($query) use ($dbDate) {
                $query->whereDate('attendance_date', $dbDate);
            }])
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
            // Pass the warning flags to frontend
            'is_off_day'   => $isOffDay,
            'off_day_name' => $dayName,
        ]);
    }

    /**
     * AJAX: Store or Update attendance
     */
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
                        'created_by' => auth()->id(), // Assuming you track who recorded it
                    ]
                );
            }
            DB::commit();
            return response()->json(['message' => 'Attendance saved successfully!', 'status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error saving data: ' . $e->getMessage(), 'status' => 'error'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
