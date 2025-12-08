<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Academic\Batch;
use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use Illuminate\Support\Facades\DB;
use App\Models\Student\StudentAttendance;
use App\Models\Payment\PaymentTransaction;

class ReportController extends Controller
{
    public function studentReport()
    {
        $branchId = auth()->user()->branch_id;

        // Simplified students query
        $students = Student::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->orderBy('student_unique_id')
            ->select('id', 'name', 'student_unique_id')
            ->get();

        return view('reports.students.index', compact('students'));
    }
    public function financeReport()
    {
        $branches = Branch::when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('id', auth()->user()->branch_id);
        })->select('id', 'branch_name', 'branch_prefix')->get();

        return view('reports.finance.index', compact('branches'));
    }

    public function financeReportGenerate(Request $request)
    {
        $request->validate([
            'date_range' => 'required|string',
            'branch_id'  => 'nullable|integer|exists:branches,id',
        ]);

        // Parse date range
        [$startDate, $endDate] = explode(' - ', $request->date_range);
        $startDate             = \Carbon\Carbon::createFromFormat('d-m-Y', trim($startDate))->startOfDay();
        $endDate               = \Carbon\Carbon::createFromFormat('d-m-Y', trim($endDate))->endOfDay();

                                                                  // Get all class names sorted ascending
        $classes = ClassName::orderBy('id')->pluck('name', 'id'); // id => name

        // Fetch transactions with student and class relation
        $query = PaymentTransaction::with(['student.class'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('is_approved', true);

        if ($request->branch_id) {
            $query->whereHas('student.branch', function ($q) use ($request) {
                $q->where('id', $request->branch_id);
            });
        }

        $transactions = $query->get();

        // Build report: { date => { class_name => amount, ... }, ... }
        $report = [];

        // Group transactions by date
        $transactionsByDate = $transactions->groupBy(function ($t) {
            return $t->created_at->format('d-m-Y');
        });

        foreach ($transactionsByDate as $date => $dailyTransactions) {
            $report[$date] = [];
            foreach ($classes as $id => $className) {
                // Sum of transactions for this class on this date, default 0
                $amount                    = $dailyTransactions->where('student.class_id', $id)->sum('amount_paid');
                $report[$date][$className] = $amount;
            }
        }

        return response()->json([
            'report'  => $report,
            'classes' => $classes->values(), // numeric array of class names
        ]);
    }

    /*
    * Attendance Report
    */
    public function attendanceReport()
    {
        $branchId = auth()->user()->branch_id;

        $branches = Branch::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('id', $branchId);
        })->select('id', 'branch_name', 'branch_prefix')->get();

        $classnames = ClassName::select('id', 'name', 'class_numeral')->get();

        $batches = Batch::with('branch:id,branch_name')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name', 'day_off', 'branch_id')
            ->get();

        return view('reports.attendance.index', compact('branches', 'classnames', 'batches'));
    }

    /*
    * Attendance AJAX Data
    */
    public function attendanceReportData(Request $request)
    {
        // --- 1. Validate and Parse Input ---
        // Validate required inputs
        $request->validate([
            'date_range' => 'required|string',
            'branch_id'  => 'required|integer|exists:branches,id',
            'class_id'   => 'required|integer|exists:class_names,id', // Added class_id validation
            'batch_id'   => 'required|integer|exists:batches,id',
        ]);

        // Parse the date range string "start_date - end_date"
        $dateRange = explode(' - ', $request->date_range);

        // Check if the range was successfully split into two parts
        if (count($dateRange) !== 2) {
            return response()->json([
                'message' => 'Invalid date range format. Expected "start_date - end_date".',
                'data'    => [],
            ], 400); // 400 Bad Request
        }

        $startDate = Carbon::parse(trim($dateRange[0]))->startOfDay();
        $endDate   = Carbon::parse(trim($dateRange[1]))->endOfDay();

        // --- 2. Build the Query ---
        $attendances = StudentAttendance::with([
            'student'   => function ($q) {
                $q->select('id', 'name', 'student_unique_id');
            },
            'batch'     => function ($q) {
                $q->select('id', 'name', 'branch_id');
            },
            'branch'    => function ($q) {
                $q->select('id', 'branch_name');
            },
            'classname' => function ($q) {
                $q->select('id', 'name', 'class_numeral');
            },
            'recorder'  => function ($q) {
                $q->select('id', 'name');
            },
        ])
            ->where('batch_id', $request->batch_id)
        // IMPORTANT: use whereBetween with the correctly parsed start and end dates
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('branch_id', $request->branch_id)
        // Added filter for class_id as per the HTML form
            ->where('class_id', $request->class_id)
            ->get();

        // --- 3. Return as JSON ---
        return response()->json([
            'message' => 'Attendance data retrieved successfully.',
            'data'    => $attendances,
        ]);
    }
}
