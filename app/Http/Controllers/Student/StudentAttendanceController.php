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
     * Classes that require academic group selection (09, 10, 11, 12)
     */
    private const GROUP_REQUIRED_CLASSES = ['09', '10', '11', '12'];

    /**
     * Available academic groups
     */
    private const ACADEMIC_GROUPS = ['Science', 'Commerce', 'Arts'];

    public function index()
    {
        $branchId = auth()->user()->branch_id;

        $branches = Branch::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('id', $branchId);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        $classnames = ClassName::select('id', 'name', 'class_numeral')->get();

        // Pass academic groups and group-required class numerals to view
        $academicGroups = self::ACADEMIC_GROUPS;
        $groupRequiredClasses = self::GROUP_REQUIRED_CLASSES;

        return view('students.attendance.index', compact(
            'branches',
            'classnames',
            'academicGroups',
            'groupRequiredClasses'
        ));
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
                        'id' => $batch->id,
                        'name' => $batch->name,
                        'day_off' => $batch->day_off,
                        'branch_name' => $batch->branch->branch_name ?? '',
                    ];
                });

            return response()->json([
                'batches' => $batches,
                'count' => $batches->count(),
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
            'branch_id' => 'required',
            'class_id' => 'required',
            'batch_id' => 'nullable', // Optional - if not provided, all batches will be loaded
            'attendance_date' => 'required',
            'academic_group' => 'nullable|in:Science,Commerce,Arts',
        ]);

        $dateCarbon = Carbon::createFromFormat('d-m-Y', $request->attendance_date);
        $dbDate = $dateCarbon->format('Y-m-d');
        $dayName = $dateCarbon->format('l');

        // Check for off-day only if specific batch is selected
        $isOffDay = false;
        if ($request->filled('batch_id')) {
            $batch = Batch::find($request->batch_id);
            if ($batch && strcasecmp($batch->day_off, $dayName) === 0) {
                $isOffDay = true;
            }
        }

        // Check if class supports academic group filtering
        $classModel = ClassName::find($request->class_id);
        $supportsGroup = $classModel && in_array($classModel->class_numeral, self::GROUP_REQUIRED_CLASSES);

        // Check if "All Groups" is selected (no specific group filter)
        $isAllGroups = $supportsGroup && !$request->filled('academic_group');

        // Check if "All Batches" is selected (no specific batch filter)
        $isAllBatches = !$request->filled('batch_id');

        // Build student query
        $studentsQuery = Student::active()
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->select('id', 'name', 'student_unique_id', 'academic_group', 'batch_id');

        // Filter by batch only if provided (optional filter)
        // When not provided, all students of the class will be loaded regardless of batch
        if ($request->filled('batch_id')) {
            $studentsQuery->where('batch_id', $request->batch_id);
        }

        // Filter by academic group only if provided (optional filter)
        // When not provided, all students of the class will be loaded regardless of group
        if ($request->filled('academic_group')) {
            $studentsQuery->where('academic_group', $request->academic_group);
        }

        $students = $studentsQuery
            ->with([
                'attendances' => function ($query) use ($dbDate) {
                    $query->whereDate('attendance_date', $dbDate)
                        ->with('recorder:id,name');
                },
                // Load home mobile number
                'mobileNumbers' => function ($query) {
                    $query->where('number_type', 'home')
                        ->select('id', 'student_id', 'mobile_number', 'number_type');
                },
                // Load batch information
                'batch:id,name',
            ])
            ->orderBy('name', 'asc')
            ->get();

        $data = $students->map(function ($student) {
            $attendance = $student->attendances->first();
            $homeMobile = $student->mobileNumbers->first();

            return [
                'id' => $student->id,
                'name' => $student->name,
                'student_unique_id' => $student->student_unique_id,
                'academic_group' => $student->academic_group,
                'batch_id' => $student->batch_id,
                'batch_name' => $student->batch ? $student->batch->name : null,
                'home_mobile' => $homeMobile ? $homeMobile->mobile_number : null,
                'status' => $attendance ? $attendance->status : null,
                'remarks' => $attendance ? $attendance->remarks : '',
                'updated_at' => $attendance ? Carbon::parse($attendance->updated_at)->format('h:i A') : null,
                'attendance_taker' => $attendance && $attendance->recorder ? $attendance->recorder->name : null,
                'has_attendance' => $attendance ? true : false,
            ];
        });

        return response()->json([
            'students' => $data,
            'count' => $data->count(),
            'is_off_day' => $isOffDay,
            'off_day_name' => $dayName,
            'supports_group' => $supportsGroup,
            'is_all_groups' => $isAllGroups,
            'is_all_batches' => $isAllBatches,
        ]);
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'attendance_date' => 'required',
            'branch_id' => 'required',
            'class_id' => 'required',
            'batch_id' => 'nullable', // Optional - each student's batch_id will be used
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required',
            'attendances.*.status' => 'required|in:present,late,absent',
            'attendances.*.batch_id' => 'nullable', // Each attendance can have its own batch_id
        ]);

        $date = Carbon::createFromFormat('d-m-Y', $request->attendance_date)->format('Y-m-d');

        DB::beginTransaction();

        try {
            foreach ($request->attendances as $att) {
                // Use individual batch_id if provided (for "All Batches" mode),
                // otherwise fall back to the request batch_id
                $batchId = $att['batch_id'] ?? $request->batch_id;

                // If still no batch_id, get it from the student record
                if (!$batchId) {
                    $student = Student::find($att['student_id']);
                    $batchId = $student ? $student->batch_id : null;
                }

                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $att['student_id'],
                        'attendance_date' => $date,
                    ],
                    [
                        'branch_id' => $request->branch_id,
                        'class_id' => $request->class_id,
                        'batch_id' => $batchId,
                        'status' => $att['status'],
                        'remarks' => $att['remarks'] ?? null,
                        'created_by' => auth()->id(),
                    ],
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Attendance saved successfully!',
                'status' => 'success',
                'updated_at' => Carbon::now()->format('h:i A'),
                'attendance_taker' => auth()->user()->name,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error saving data: ' . $e->getMessage(), 'status' => 'error'], 500);
        }
    }
}
