<?php
namespace App\Http\Controllers;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\Payment\Cost;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use App\Models\Student\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /*
     * Attendance Report
     */
    public function attendanceReport()
    {
        $branchId = auth()->user()->branch_id;

        $branches = Branch::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('id', $branchId);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

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
            return response()->json(
                [
                    'message' => 'Invalid date range format. Expected "start_date - end_date".',
                    'data'    => [],
                ],
                400,
            ); // 400 Bad Request
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

    /**
     * Finance report page
     */
    public function financeReport()
    {
        $user    = Auth::user();
        $isAdmin = ! $user->branch_id;

        $branches = Branch::when(! $isAdmin, function ($q) use ($user) {
            $q->where('id', $user->branch_id);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        return view('reports.finance.index', compact('branches', 'isAdmin'));
    }

    /**
     * Generate finance report
     */
    public function financeReportGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'date_range' => 'required|string',
            'branch_id'  => 'nullable|integer|exists:branches,id',
        ]);

        try {
            [$start, $end] = explode(' - ', $request->date_range);
            $startDate     = Carbon::createFromFormat('d-m-Y', trim($start))->startOfDay();
            $endDate       = Carbon::createFromFormat('d-m-Y', trim($end))->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format',
            ], 422);
        }

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        $classes = ClassName::orderBy('id')->pluck('name', 'id');

        $transactions = PaymentTransaction::with('student')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_approved', true)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('student.branch', fn($b) => $b->where('id', $branchId));
            })
            ->get();

        $costs = Cost::betweenDates($startDate->toDateString(), $endDate->toDateString())
            ->forBranch($branchId)
            ->get()
            ->keyBy(fn($c) => $c->cost_date->format('d-m-Y'));

        $transactionsByDate = $transactions->groupBy(fn($t) => $t->created_at->format('d-m-Y'));

        $dates  = collect();
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $d = $cursor->format('d-m-Y');
            if ($transactionsByDate->has($d) || $costs->has($d)) {
                $dates->push($d);
            }
            $cursor->addDay();
        }

        $report     = [];
        $costReport = [];

        foreach ($dates as $date) {
            $dailyTx = $transactionsByDate->get($date, collect());
            foreach ($classes as $id => $name) {
                $report[$date][$name] = (float) $dailyTx->where('student.class_id', $id)->sum('amount_paid');
            }
            $costReport[$date] = (float) optional($costs->get($date))->amount ?? 0;
        }

        return response()->json([
            'success' => true,
            'report'  => $report,
            'costs'   => $costReport,
            'classes' => $classes->values(),
        ]);
    }

    /**
     * Load cost list (AJAX)
     */
    public function getReportCosts(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date_format:d-m-Y',
            'end_date'   => 'nullable|date_format:d-m-Y',
            'branch_id'  => 'nullable|exists:branches,id',
        ]);

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        $query = Cost::with(['branch:id,branch_name,branch_prefix', 'createdBy:id,name'])
            ->forBranch($branchId);

        if ($request->start_date && $request->end_date) {
            $query->betweenDates(
                Carbon::createFromFormat('d-m-Y', $request->start_date)->toDateString(),
                Carbon::createFromFormat('d-m-Y', $request->end_date)->toDateString()
            );
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('cost_date', 'desc')->get(),
        ]);
    }
}
