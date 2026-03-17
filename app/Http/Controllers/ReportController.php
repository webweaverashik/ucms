<?php
namespace App\Http\Controllers;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\Cost\Cost;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Classes that require academic group selection (09, 10, 11, 12)
     */
    private const GROUP_REQUIRED_CLASSES = ['09', '10', '11', '12'];

    /**
     * Available academic groups
     */
    private const ACADEMIC_GROUPS = ['Science', 'Commerce', 'Arts'];

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

        // Pass academic groups and group-required class numerals to view
        $academicGroups       = self::ACADEMIC_GROUPS;
        $groupRequiredClasses = self::GROUP_REQUIRED_CLASSES;

        return view('reports.attendance.index', compact(
            'branches',
            'classnames',
            'batches',
            'academicGroups',
            'groupRequiredClasses'
        ));
    }

    /*
     * Attendance AJAX Data
     */
    public function attendanceReportData(Request $request)
    {
        // --- 1. Validate and Parse Input ---
        // Validate required inputs
        $request->validate([
            'date_range'     => 'required|string',
            'branch_id'      => 'required|integer|exists:branches,id',
            'class_id'       => 'required|integer|exists:class_names,id',
            'batch_id'       => 'required|integer|exists:batches,id',
            'academic_group' => 'nullable|in:Science,Commerce,Arts',
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
            );
        }

        $startDate = Carbon::parse(trim($dateRange[0]))->startOfDay();
        $endDate   = Carbon::parse(trim($dateRange[1]))->endOfDay();

        // Get the class to check if it requires academic group
        $classModel    = ClassName::find($request->class_id);
        $supportsGroup = $classModel && in_array($classModel->class_numeral, self::GROUP_REQUIRED_CLASSES);

        // Check if "All Groups" is selected (no specific group filter)
        $isAllGroups = $supportsGroup && ! $request->filled('academic_group');

        // --- 2. Build the Query ---
        $attendances = StudentAttendance::with([
            'student'   => function ($q) use ($request, $supportsGroup) {
                $q->select('id', 'name', 'student_unique_id', 'academic_group', 'class_id');
                // Filter by academic group if provided and class supports it
                if ($supportsGroup && $request->filled('academic_group')) {
                    $q->where('academic_group', $request->academic_group);
                }
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
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id);

        // Filter attendances by student's academic group if provided
        if ($supportsGroup && $request->filled('academic_group')) {
            $attendances->whereHas('student', function ($q) use ($request) {
                $q->where('academic_group', $request->academic_group);
            });
        }

        $attendances = $attendances->get();

        // --- 3. Return as JSON ---
        return response()->json([
            'message'        => 'Attendance data retrieved successfully.',
            'data'           => $attendances,
            'supports_group' => $supportsGroup,
            'is_all_groups'  => $isAllGroups,
            'date_range'     => $request->date_range,
        ]);
    }

    /**
     * Finance report page (Revenue vs Cost)
     */
    public function financeReportIndex()
    {
        $user = auth()->user();

        if (! $user->isAdmin() && ! $user->isManager()) {
            return redirect()->route('reports.cost-records.index')->with('error', 'Unauthorized access to this page.');
        }

        $isAdmin = $user->isAdmin();

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
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid date format',
                ],
                422,
            );
        }

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        // Get transactions with relationships
        $transactions = PaymentTransaction::with(['student', 'createdBy'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_approved', true)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('student.branch', fn($b) => $b->where('id', $branchId));
            })
            ->get();

        // Get class IDs with transactions
        $classIdsWithRevenue = $transactions->pluck('student.class_id')->filter()->unique()->values()->toArray();

        // Get classes data
        $classesData = ClassName::orderBy('id')
            ->where(function ($query) use ($classIdsWithRevenue) {
                $query->active()
                    ->orWhereIn('id', $classIdsWithRevenue);
            })
            ->select('id', 'name', 'is_active')
            ->get();

        $classes     = $classesData->pluck('name', 'id');
        $classesInfo = $classesData
            ->map(
                fn($class) => [
                    'id'        => $class->id,
                    'name'      => $class->name,
                    'is_active' => $class->is_active,
                ],
            )
            ->values();

        // Get collectors
        $collectors = $transactions->pluck('createdBy')->filter()->unique('id')->sortBy('name')->mapWithKeys(fn($user) => [$user->id => $user->name]);

        // Get costs with entries
        $costs = Cost::with('entries')->betweenDates($startDate->toDateString(), $endDate->toDateString())->forBranch($branchId)->get()->keyBy(fn($c) => $c->cost_date->format('d-m-Y'));

        $transactionsByDate = $transactions->groupBy(fn($t) => $t->created_at->format('d-m-Y'));

        // Get dates with data
        $dates  = collect();
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $d = $cursor->format('d-m-Y');
            if ($transactionsByDate->has($d) || $costs->has($d)) {
                $dates->push($d);
            }
            $cursor->addDay();
        }

        $report          = [];
        $costReport      = [];
        $collectorReport = [];

        // Initialize class totals
        $classTotals = [];
        foreach ($classes as $id => $name) {
            $classTotals[$name] = 0;
        }

        foreach ($dates as $date) {
            $dailyTx = $transactionsByDate->get($date, collect());

            // Class-wise revenue
            foreach ($classes as $id => $name) {
                $amount                = (float) $dailyTx->where('student.class_id', $id)->sum('amount_paid');
                $report[$date][$name]  = $amount;
                $classTotals[$name]   += $amount;
            }

            // Collector-wise collection
            foreach ($collectors as $collectorId => $collectorName) {
                $collectorReport[$date][$collectorId] = (float) $dailyTx->where('created_by', $collectorId)->sum('amount_paid');
            }

            // Cost total from entries
            $cost              = $costs->get($date);
            $costReport[$date] = $cost ? (float) $cost->totalAmount() : 0;
        }

        return response()->json([
            'success'         => true,
            'report'          => $report,
            'costs'           => $costReport,
            'classes'         => $classes->values(),
            'classesInfo'     => $classesInfo,
            'classTotals'     => $classTotals,
            'collectors'      => $collectors,
            'collectorReport' => $collectorReport,
        ]);
    }

    /**
     * Cost Records page
     */
    public function costRecordsIndex()
    {
        $user = Auth::user();

        $isAdmin = $user->isAdmin();

        $branches = Branch::when(! $isAdmin, function ($q) use ($user) {
            $q->where('id', $user->branch_id);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        return view('reports.cost-records.index', compact('branches', 'isAdmin'));
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

        $user = Auth::user();

        // Determine branch filter
        if ($user->isAdmin()) {
            // Admin can filter by specific branch or see all
            $branchId = $request->branch_id;
        } else {
            // Non-admin users can only see their own branch
            $branchId = $user->branch_id;
        }

        $query = Cost::with([
            'branch:id,branch_name,branch_prefix',
            'createdBy:id,name',
            'entries.costType:id,name',
        ]);

        // Apply branch filter
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->start_date && $request->end_date) {
            $query->betweenDates(
                Carbon::createFromFormat('d-m-Y', $request->start_date)->toDateString(),
                Carbon::createFromFormat('d-m-Y', $request->end_date)->toDateString()
            );
        }

        $costs = $query->orderBy('cost_date', 'desc')->get();

        // Add total_amount to each cost
        $costs->each(function ($cost) {
            $cost->total_amount = $cost->totalAmount();
        });

        return response()->json([
            'success' => true,
            'data'    => $costs,
        ]);
    }

    /**
     * Annual Due Report page
     */
    public function annualDueReportIndex()
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized access to the page.');
        }

        $branches = Branch::select('id', 'branch_name', 'branch_prefix')->get();

        return view('reports.annual-due.index', compact('branches'));
    }

    /**
     * Annual Due Report – AJAX Data
     *
     * Returns two separate datasets:
     *
     * 1. TUITION FEE dues  → uses `month_year` column (format: MM_YYYY)
     *    Grouped by month_year + class + batch
     *
     * 2. OTHER FEE dues    → uses `created_at` to determine the month
     *    (month_year is NULL for non-Tuition invoices)
     *    Grouped by created_at month + invoice_type + class + batch
     *
     * Base query (matches raw SQL):
     *   SELECT SUM(amount_due) FROM payment_invoices
     *   WHERE status != 'paid' AND deleted_at IS NULL
     *   AND student_id IN (SELECT id FROM students WHERE branch_id = ?);
     */
    public function annualDueReportData(Request $request): JsonResponse
    {
        // Only admin can access
        if (! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'year'      => 'required|integer|min:2020|max:2099',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $year     = (int) $request->year;
        $branchId = (int) $request->branch_id;
        $branch   = Branch::find($branchId);

        $monthNames = [
            1  => 'January', 2  => 'February', 3  => 'March',
            4  => 'April', 5    => 'May', 6       => 'June',
            7  => 'July', 8     => 'August', 9    => 'September',
            10 => 'October', 11 => 'November', 12 => 'December',
        ];

        // Build all possible month_year values for the selected year (01_2025 … 12_2025)
        $monthYearValues = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthYearValues[] = str_pad($m, 2, '0', STR_PAD_LEFT) . '_' . $year;
        }

        // Student IDs subquery — includes soft-deleted students (matches raw SQL)
        $studentIdsSubquery = function ($query) use ($branchId) {
            $query->select('id')->from('students')->where('branch_id', $branchId);
        };

        // Get Tuition Fee type ID
        $tuitionTypeId = PaymentInvoiceType::where('type_name', 'Tuition Fee')->value('id');

        // ════════════════════════════════════════════════════════════
        //  SECTION 1: TUITION FEE DUES (month_year column)
        // ════════════════════════════════════════════════════════════

        // Query ALL tuition invoices (both paid and unpaid) to get collectable amounts
        $allTuitionInvoices = PaymentInvoice::whereIn('student_id', $studentIdsSubquery)
            ->when($tuitionTypeId, fn($q) => $q->where('invoice_type_id', $tuitionTypeId))
            ->whereIn('month_year', $monthYearValues)
            ->with([
                'student'       => fn($q)       => $q->withTrashed()->select('id', 'name', 'class_id', 'batch_id'),
                'student.class' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'student.batch' => fn($q) => $q->select('id', 'name'),
            ])
            ->get();

        // Filter unpaid invoices for due calculation
        $tuitionInvoices = $allTuitionInvoices->filter(fn($inv) => $inv->status !== 'paid');

        $tuitionGrandTotal       = $tuitionInvoices->sum('amount_due');
        $tuitionTotalInvoices    = $tuitionInvoices->count();
        $tuitionCollectableTotal = $allTuitionInvoices->sum('total_amount');

        // ── Pre-populate tuition summary with ALL active classes ──
        $allActiveClasses = ClassName::active()->orderBy('id')->select('id', 'name')->get();

        // Each month now has 'collectable' and 'due' sub-keys
        $tuitionSummary = [];
        foreach ($allActiveClasses as $cls) {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[$m] = ['collectable' => 0, 'due' => 0];
            }
            $tuitionSummary[$cls->name] = ['class_id' => $cls->id, 'months' => $months];
        }

        // Track monthly totals for collectable
        $tuitionMonthlyCollectable = array_fill(1, 12, 0);

        // Process ALL invoices for collectable
        foreach ($allTuitionInvoices as $inv) {
            $parts     = explode('_', $inv->month_year);
            $monthNum  = (int) $parts[0];
            $className = $inv->student->class->name ?? 'Unknown';
            $classId   = $inv->student->class_id ?? 0;

            // Ensure class exists in summary (for inactive classes)
            if (! isset($tuitionSummary[$className])) {
                $months = [];
                for ($m = 1; $m <= 12; $m++) {
                    $months[$m] = ['collectable' => 0, 'due' => 0];
                }
                $tuitionSummary[$className] = ['class_id' => (int) $classId, 'months' => $months];
            }

            // Add to collectable (all invoices)
            $tuitionSummary[$className]['months'][$monthNum]['collectable'] += $inv->total_amount;
            $tuitionMonthlyCollectable[$monthNum]                           += $inv->total_amount;

            // Add to due only if unpaid
            if ($inv->status !== 'paid') {
                $tuitionSummary[$className]['months'][$monthNum]['due'] += $inv->amount_due;
            }
        }

        // Group UNPAID invoices by month + class + batch for detailed view
        $tuitionGrouped = $tuitionInvoices->groupBy(function ($inv) {
            $parts = explode('_', $inv->month_year);
            return (int) $parts[0]
                . '|' . ($inv->student->class_id ?? 0)
                . '|' . ($inv->student->batch_id ?? 0);
        });

        $tuitionDetailed = [];

        foreach ($tuitionGrouped as $key => $invoices) {
            [$monthNum, $classId, $batchId] = explode('|', $key);
            $monthNum                       = (int) $monthNum;

            $first      = $invoices->first();
            $className  = $first->student->class->name ?? 'Unknown';
            $batchName  = $first->student->batch->name ?? 'Unknown';
            $dueAmount  = (int) $invoices->sum('amount_due');
            $studentCnt = $invoices->pluck('student_id')->unique()->count();

            $tuitionDetailed[] = [
                'month'         => $monthNames[$monthNum],
                'month_num'     => $monthNum,
                'class'         => $className,
                'class_id'      => (int) $classId,
                'batch'         => $batchName,
                'batch_id'      => (int) $batchId,
                'student_count' => $studentCnt,
                'due_amount'    => $dueAmount,
            ];
        }

        // Sort detailed by month → class → batch
        usort($tuitionDetailed, fn($a, $b) =>
            $a['month_num'] <=> $b['month_num']
                ?: strcmp($a['class'], $b['class'])
                ?: strcmp($a['batch'], $b['batch'])
        );

        // Format tuition summary with collectable and due
        $fmtTuitionSummary = [];
        uasort($tuitionSummary, fn($a, $b) => $a['class_id'] <=> $b['class_id']);

        foreach ($tuitionSummary as $className => $data) {
            $monthData        = [];
            $totalCollectable = 0;
            $totalDue         = 0;

            foreach ($data['months'] as $m => $amounts) {
                $monthData[$monthNames[$m]]  = [
                    'collectable' => $amounts['collectable'],
                    'due'         => $amounts['due'],
                ];
                $totalCollectable += $amounts['collectable'];
                $totalDue         += $amounts['due'];
            }

            $fmtTuitionSummary[$className] = [
                'class_id'          => $data['class_id'],
                'months'            => $monthData,
                'total_collectable' => $totalCollectable,
                'total_due'         => $totalDue,
            ];
        }

        // Format monthly collectable totals
        $fmtMonthlyCollectable = [];
        foreach ($tuitionMonthlyCollectable as $m => $amt) {
            $fmtMonthlyCollectable[$monthNames[$m]] = $amt;
        }

        // ════════════════════════════════════════════════════════════
        //  SECTION 2: OTHER INVOICE TYPE DUES (created_at month)
        // ════════════════════════════════════════════════════════════

        $otherQuery = PaymentInvoice::where('status', '!=', 'paid')
            ->whereIn('student_id', $studentIdsSubquery)
            ->whereYear('created_at', $year)
            ->with([
                'student'       => fn($q)       => $q->withTrashed()->select('id', 'name', 'class_id', 'batch_id'),
                'student.class' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'student.batch' => fn($q) => $q->select('id', 'name'),
                'invoiceType:id,type_name',
            ]);

        if ($tuitionTypeId) {
            $otherQuery->where('invoice_type_id', '!=', $tuitionTypeId);
        }

        $otherInvoices      = $otherQuery->get();
        $otherGrandTotal    = $otherInvoices->sum('amount_due');
        $otherTotalInvoices = $otherInvoices->count();

        // Group by month(created_at) + invoice_type + class + batch
        $otherGrouped = $otherInvoices->groupBy(function ($inv) {
            return $inv->created_at->month
            . '|' . $inv->invoice_type_id
                . '|' . ($inv->student->class_id ?? 0)
                . '|' . ($inv->student->batch_id ?? 0);
        });

        $otherDetailed = [];
        $otherSummary  = []; // typeName => [1 => amount, …, 12 => amount]

        foreach ($otherGrouped as $key => $invoices) {
            [$monthNum, $typeId, $classId, $batchId] = explode('|', $key);
            $monthNum                                = (int) $monthNum;

            $first      = $invoices->first();
            $typeName   = $first->invoiceType->type_name ?? 'Unknown';
            $className  = $first->student->class->name ?? 'Unknown';
            $batchName  = $first->student->batch->name ?? 'Unknown';
            $dueAmount  = (int) $invoices->sum('amount_due');
            $studentCnt = $invoices->pluck('student_id')->unique()->count();

            $otherDetailed[] = [
                'month'           => $monthNames[$monthNum],
                'month_num'       => $monthNum,
                'invoice_type'    => $typeName,
                'invoice_type_id' => (int) $typeId,
                'class'           => $className,
                'class_id'        => (int) $classId,
                'batch'           => $batchName,
                'batch_id'        => (int) $batchId,
                'student_count'   => $studentCnt,
                'due_amount'      => $dueAmount,
            ];

            if (! isset($otherSummary[$typeName])) {
                $otherSummary[$typeName] = array_fill(1, 12, 0);
            }
            $otherSummary[$typeName][$monthNum] += $dueAmount;
        }

        // Sort other detailed by month → invoice_type → class
        usort($otherDetailed, fn($a, $b) =>
            $a['month_num'] <=> $b['month_num']
                ?: strcmp($a['invoice_type'], $b['invoice_type'])
                ?: strcmp($a['class'], $b['class'])
        );

        // Format other summary
        $fmtOtherSummary = [];
        ksort($otherSummary);

        foreach ($otherSummary as $typeName => $months) {
            $monthData = [];
            foreach ($months as $m => $amt) {
                $monthData[$monthNames[$m]] = $amt;
            }
            $fmtOtherSummary[$typeName] = [
                'months' => $monthData,
                'total'  => array_sum($months),
            ];
        }

        // ════════════════════════════════════════════════════════════
        //  RESPONSE
        // ════════════════════════════════════════════════════════════

        return response()->json([
            'success'        => true,

            // Tuition Fee section
            'tuition'        => [
                'detailed'            => $tuitionDetailed,
                'summary'             => $fmtTuitionSummary,
                'monthly_collectable' => $fmtMonthlyCollectable,
                'grand_total'         => $tuitionGrandTotal,
                'collectable_total'   => $tuitionCollectableTotal,
                'total_invoices'      => $tuitionTotalInvoices,
                'total_classes'       => count($fmtTuitionSummary),
            ],

            // Other Fee Types section
            'other'          => [
                'detailed'       => $otherDetailed,
                'summary'        => $fmtOtherSummary,
                'grand_total'    => $otherGrandTotal,
                'total_invoices' => $otherTotalInvoices,
                'total_types'    => count($fmtOtherSummary),
            ],

            // Combined totals
            'grand_total'    => $tuitionGrandTotal + $otherGrandTotal,
            'total_invoices' => $tuitionTotalInvoices + $otherTotalInvoices,

            // Branch / Year info
            'branch_name'    => $branch->branch_name ?? '',
            'branch_prefix'  => $branch->branch_prefix ?? '',
            'year'           => $year,
        ]);
    }

    /**
     * Annual Due Report – Invoice Details (AJAX)
     *
     * Returns individual invoices for a specific group
     * (month + class + batch for tuition, or month + invoice_type + class + batch for other fees)
     *
     * Used by the modal when clicking on the student count badge.
     */
    public function annualDueInvoices(Request $request): JsonResponse
    {
        // Only admin can access
        if (! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type'            => 'required|in:tuition,other',
            'year'            => 'required|integer|min:2020|max:2099',
            'branch_id'       => 'required|integer|exists:branches,id',
            'month_num'       => 'required|integer|min:1|max:12',
            'class_id'        => 'required|integer',
            'batch_id'        => 'required|integer',
            'invoice_type_id' => 'nullable|integer|exists:payment_invoice_types,id',
        ]);

        $year     = (int) $request->year;
        $branchId = (int) $request->branch_id;
        $classId  = (int) $request->class_id;
        $batchId  = (int) $request->batch_id;
        $monthNum = (int) $request->month_num;

        $tuitionTypeId = PaymentInvoiceType::where('type_name', 'Tuition Fee')->value('id');

        // Get student IDs matching branch + class + batch (includes soft-deleted — no whereNull on deleted_at)
        $studentIds = DB::table('students')
            ->where('branch_id', $branchId)
            ->where('class_id', $classId)
            ->where('batch_id', $batchId)
            ->pluck('id');

        $query = PaymentInvoice::where('status', '!=', 'paid')
            ->whereIn('student_id', $studentIds)
            ->with(['student' => fn($q) => $q->withTrashed()->select('id', 'name', 'student_unique_id')])
            ->select('id', 'invoice_number', 'student_id', 'amount_due', 'invoice_type_id');

        if ($request->type === 'tuition') {
            // Tuition Fee: match by month_year column
            $monthYear = str_pad($monthNum, 2, '0', STR_PAD_LEFT) . '_' . $year;

            $query->where('invoice_type_id', $tuitionTypeId)
                ->where('month_year', $monthYear);
        } else {
            // Other Fees: match by created_at year + month
            if ($tuitionTypeId) {
                $query->where('invoice_type_id', '!=', $tuitionTypeId);
            }

            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum);

            // Optionally filter by specific invoice type
            if ($request->filled('invoice_type_id')) {
                $query->where('invoice_type_id', $request->invoice_type_id);
            }
        }

        $invoices = $query->orderBy('id')->get();

        return response()->json([
            'success'        => true,
            'data'           => $invoices->map(function ($inv, $idx) {
                return [
                    'sl'             => $idx + 1,
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'student_id'     => $inv->student_id,
                    'student_name'   => $inv->student->name ?? 'N/A',
                    'student_uid'    => $inv->student->student_unique_id ?? '',
                    'amount_due'     => (int) $inv->amount_due,
                ];
            })->values(),
            'total_due'      => (int) $invoices->sum('amount_due'),
            'total_invoices' => $invoices->count(),
        ]);
    }
}
