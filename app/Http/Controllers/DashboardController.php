<?php
namespace App\Http\Controllers;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\Cost\Cost;
use App\Models\Cost\CostEntry;
use App\Models\Cost\CostType;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use App\Models\Student\StudentAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role
     */
    public function index()
    {
        $user = auth()->user();

        foreach (['admin', 'manager', 'accountant'] as $role) {
            if ($user->hasRole($role)) {
                $branches = $user->isAdmin() ? Branch::orderBy('branch_name')->get() : collect();
                $isAdmin  = $user->isAdmin();

                // For admin, get first branch ID; for others, use their branch
                $branchId = $user->isAdmin()
                    ? ($branches->first()?->id ?? null)
                    : $user->branch_id;

                return view("dashboard.{$role}.index", compact('branchId', 'branches', 'isAdmin'));
            }
        }

        abort(403, 'Unauthorized access');
    }

    /**
     * Get student statistics
     */
    public function getStudentStats(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);

        $query = Student::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Total students must be in active classes and not pending (has activation)
        $totalStudents = (clone $query)
            ->whereNotNull('student_activation_id')
            ->whereHas('class', fn($q) => $q->active())
            ->count();

        // Active students must be in active classes
        $activeStudents = (clone $query)
            ->active()
            ->whereHas('class', fn($q) => $q->active())
            ->count();

        $pendingStudents = (clone $query)->pending()->count();

        $inactiveStudents = (clone $query)
            ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'inactive'))
            ->count();

        $newStudentsThisWeek = Student::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'         => $totalStudents,
                'active'        => $activeStudents,
                'pending'       => $pendingStudents,
                'inactive'      => $inactiveStudents,
                'new_this_week' => $newStudentsThisWeek,
            ],
        ]);
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStats(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);

        $studentQuery = function ($query) use ($branchId) {
            $query->withoutTrashed();
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        };

        $dueInvoices = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', $studentQuery)
            ->get();

        $totalDueCount  = $dueInvoices->count();
        $totalDueAmount = $dueInvoices->sum('amount_due');

        $topDueStudents = PaymentInvoice::select('student_id', DB::raw('SUM(amount_due) as total_due'), DB::raw('COUNT(*) as invoice_count'))
            ->where('status', '!=', 'paid')
            ->whereHas('student', $studentQuery)
            ->groupBy('student_id')
            ->orderByDesc('total_due')
            ->limit(10)
            ->with('student:id,name,student_unique_id,branch_id')
            ->get()
            ->map(fn($item) => [
                'student_id'        => $item->student_id,
                'name'              => $item->student?->name ?? 'Unknown',
                'student_unique_id' => $item->student?->student_unique_id ?? '',
                'invoice_count'     => $item->invoice_count,
                'total_due'         => $item->total_due,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'total_due_count'  => $totalDueCount,
                'total_due_amount' => $totalDueAmount,
                'top_due_students' => $topDueStudents,
            ],
        ]);
    }

    /**
     * Get pending discounted transactions (Admin only)
     */
    public function getPendingDiscountedTransactions(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $branchId = $request->get('branch_id');

        $transactions = PaymentTransaction::with([
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
            'paymentInvoice:id,invoice_number',
            'createdBy:id,name',
        ])
            ->where('payment_type', 'discounted')
            ->where('is_approved', false)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId));
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($txn) => [
                'id'             => $txn->id,
                'voucher_no'     => $txn->voucher_no,
                'amount_paid'    => $txn->amount_paid,
                'student_name'   => $txn->student?->name ?? 'Unknown',
                'student_id'     => $txn->student?->student_unique_id ?? '',
                'branch'         => $txn->student?->branch?->branch_name ?? '',
                'invoice_id'     => $txn->paymentInvoice?->id,
                'invoice_number' => $txn->paymentInvoice?->invoice_number ?? '',
                'created_by'     => $txn->createdBy?->name ?? 'System',
                'created_at'     => $txn->created_at->format('d M Y, h:i A'),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * Get collection statistics
     */
    public function getCollectionStats(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);
        $isAdmin  = $user->isAdmin();

        // Get student IDs for branch filtering
        $studentIds = null;
        if ($branchId) {
            $studentIds = Student::where('branch_id', $branchId)->pluck('id')->toArray();
        }

        // Get date from request or use today
        $selectedDate = $request->get('date') ? Carbon::parse($request->get('date'))->toDateString() : Carbon::today()->toDateString();
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        // Build base query for transactions
        $baseQuery = PaymentTransaction::where('is_approved', true);
        if (! empty($studentIds)) {
            $baseQuery->whereIn('student_id', $studentIds);
        }

        $totalCollection = (clone $baseQuery)->sum('amount_paid');

        $selectedDateCollection = (clone $baseQuery)
            ->whereDate('created_at', $selectedDate)
            ->sum('amount_paid');

        $monthCollection = (clone $baseQuery)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount_paid');

        // Hourly collection query for selected date
        $hourlyQuery = PaymentTransaction::query()
            ->selectRaw('HOUR(created_at) as hour, SUM(amount_paid) as total')
            ->where('is_approved', true)
            ->whereDate('created_at', $selectedDate);

        if (! empty($studentIds)) {
            $hourlyQuery->whereIn('student_id', $studentIds);
        }

        $hourlyResults = $hourlyQuery
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();

        // Build hourly collection map
        $hourlyCollection = [];
        foreach ($hourlyResults as $row) {
            $hour                    = (int) $row->hour;
            $hourlyCollection[$hour] = (float) $row->total;
        }

        // Determine hour range based on actual data
        // Default: 6 AM to 11 PM (typical coaching hours)
        $minHour = 6;
        $maxHour = 23;

        // If there's data outside this range, expand to include it
        if (! empty($hourlyCollection)) {
            $dataMinHour = min(array_keys($hourlyCollection));
            $dataMaxHour = max(array_keys($hourlyCollection));
            $minHour     = min($minHour, $dataMinHour);
            $maxHour     = max($maxHour, $dataMaxHour);
        }

        // Generate hourly data for chart
        $hourlyData = [];
        for ($i = $minHour; $i <= $maxHour; $i++) {
            // Format hour in 12-hour format for better readability
            $hourLabel    = $i == 0 ? '12 AM' : ($i < 12 ? $i . ' AM' : ($i == 12 ? '12 PM' : ($i - 12) . ' PM'));
            $hourlyData[] = [
                'hour'   => $hourLabel,
                'amount' => $hourlyCollection[$i] ?? 0,
            ];
        }

        // User wise collection for selected date with user IDs for linking
        $userWiseQuery = PaymentTransaction::where('is_approved', true)
            ->whereDate('created_at', $selectedDate);

        if (! empty($studentIds)) {
            $userWiseQuery->whereIn('student_id', $studentIds);
        }

        $userWiseResults = $userWiseQuery
            ->selectRaw('created_by, SUM(amount_paid) as total')
            ->groupBy('created_by')
            ->orderByDesc('total')
            ->get();

        $userWiseCollection = [];
        foreach ($userWiseResults as $item) {
            $createdBy            = User::find($item->created_by);
            $userWiseCollection[] = [
                'user_id'   => $item->created_by,
                'user_name' => $createdBy?->name ?? 'System',
                'total'     => (float) $item->total,
            ];
        }

        // Format date for display
        $displayDate = Carbon::parse($selectedDate)->format('d M Y');
        $isToday     = $selectedDate === Carbon::today()->toDateString();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_collection'         => (float) $totalCollection,
                'selected_date_collection' => (float) $selectedDateCollection,
                'month_collection'         => (float) $monthCollection,
                'hourly_collection'        => $hourlyData,
                'user_wise_collection'     => $userWiseCollection,
                'is_admin'                 => $isAdmin,
                'selected_date'            => $selectedDate,
                'display_date'             => $displayDate,
                'is_today'                 => $isToday,
            ],
        ]);
    }

    /**
     * Get cost statistics
     */
    public function getCostStats(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);
        $period   = $request->get('period', 'month');

        $startDate = match ($period) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year'  => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $endDate = Carbon::now();

        $costsQuery = Cost::with(['entries.costType', 'branch:id,branch_name'])
            ->whereBetween('cost_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $costs = $costsQuery->get();

        $totalCost = $costs->sum(fn($cost) => $cost->entries->sum('amount'));

        $costTypeBreakdown = CostEntry::whereHas('cost', function ($q) use ($startDate, $endDate, $branchId) {
            $q->whereBetween('cost_date', [$startDate, $endDate]);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })
            ->with('costType:id,name')
            ->selectRaw('cost_type_id, SUM(amount) as total')
            ->groupBy('cost_type_id')
            ->get()
            ->map(fn($item) => [
                'type_id'   => $item->cost_type_id,
                'type_name' => $item->costType?->name ?? 'Unknown',
                'total'     => $item->total,
            ]);

        $costTypes = CostType::active()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => [
                'total_cost'          => $totalCost,
                'cost_type_breakdown' => $costTypeBreakdown,
                'cost_types'          => $costTypes,
                'period'              => $period,
                'start_date'          => $startDate->format('d M Y'),
                'end_date'            => $endDate->format('d M Y'),
            ],
        ]);
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);

        $transactions = PaymentTransaction::with([
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
            'paymentInvoice:id,invoice_number',
            'createdBy:id,name',
        ])
            ->where('is_approved', true)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId));
            })
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn($txn) => [
                'id'              => $txn->id,
                'voucher_no'      => $txn->voucher_no,
                'amount_paid'     => (float) $txn->amount_paid,
                'payment_type'    => $txn->payment_type,
                'student_name'    => $txn->student?->name ?? 'Unknown',
                'student_id'      => $txn->student?->student_unique_id ?? '',
                'branch'          => $txn->student?->branch?->branch_name ?? '',
                'invoice_id'      => $txn->paymentInvoice?->id,
                'invoice_number'  => $txn->paymentInvoice?->invoice_number ?? '',
                'created_by'      => $txn->createdBy?->name ?? 'System',
                'created_at'      => $txn->created_at->format('d M Y, h:i A'),
                'created_at_diff' => $txn->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);

        // Get date from request or use today
        $filterDate = $request->get('date')
            ? Carbon::parse($request->get('date'))
            : Carbon::today();

        // Get batch filter
        $batchId = $request->get('batch_id');

        // Base query for the selected date
        $baseQuery = StudentAttendance::whereDate('attendance_date', $filterDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // Today's attendance summary (always for selected date, all batches)
        $summaryQuery = StudentAttendance::whereDate('attendance_date', $filterDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $todayAttendance = $summaryQuery
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Class-wise attendance for selected date with optional batch filter
        $classQuery = StudentAttendance::whereDate('attendance_date', $filterDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
            ->with('classname:id,name,class_numeral')
            ->selectRaw('class_id, status, COUNT(*) as count')
            ->groupBy('class_id', 'status')
            ->get();

        $classData = [];
        foreach ($classQuery as $item) {
            $classId = $item->class_id;
            if (! isset($classData[$classId])) {
                $classData[$classId] = [
                    'class_id'      => $classId,
                    'class_name'    => $item->classname?->name ?? 'Unknown',
                    'class_numeral' => $item->classname?->class_numeral ?? '',
                    'present'       => 0,
                    'absent'        => 0,
                    'late'          => 0,
                ];
            }
            $classData[$classId][$item->status] = (int) $item->count;
        }

        // Get batches for tabs
        $batches = Batch::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get classes for filter
        $classes = ClassName::active()
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('students', fn($sq) => $sq->where('branch_id', $branchId));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'class_numeral']);

        // Format date for display
        $displayDate = $filterDate->format('d M Y');
        $isToday     = $filterDate->toDateString() === Carbon::today()->toDateString();

        return response()->json([
            'success' => true,
            'data'    => [
                'class_wise'   => array_values($classData),
                'today'        => [
                    'present' => (int) ($todayAttendance['present'] ?? 0),
                    'absent'  => (int) ($todayAttendance['absent'] ?? 0),
                    'late'    => (int) ($todayAttendance['late'] ?? 0),
                ],
                'filters'      => [
                    'classes' => $classes,
                    'batches' => $batches,
                ],
                'filter_date'  => $filterDate->format('Y-m-d'),
                'display_date' => $displayDate,
                'is_today'     => $isToday,
            ],
        ]);
    }

    /**
     * Get dashboard summary (all stats in one call for initial load)
     */
    public function getSummary(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $this->resolveBranchId($request, $user);

        $studentQuery = Student::query()->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $studentStats = [
            'total'   => (clone $studentQuery)->whereNotNull('student_activation_id')->whereHas('class', fn($q) => $q->active())->count(),
            'active'  => (clone $studentQuery)->active()->whereHas('class', fn($q) => $q->active())->count(),
            'pending' => (clone $studentQuery)->pending()->count(),
        ];

        $studentConstraint = function ($query) use ($branchId) {
            $query->withoutTrashed();
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        };

        $dueInvoices = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', $studentConstraint);

        $invoiceStats = [
            'due_count'  => $dueInvoices->count(),
            'due_amount' => $dueInvoices->sum('amount_due'),
        ];

        $transactionConstraint = function ($q) use ($branchId) {
            if ($branchId) {
                $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId));
            }
        };

        $collectionStats = [
            'today' => PaymentTransaction::where('is_approved', true)
                ->whereDate('created_at', Carbon::today())
                ->when($branchId, $transactionConstraint)
                ->sum('amount_paid'),
            'month' => PaymentTransaction::where('is_approved', true)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->when($branchId, $transactionConstraint)
                ->sum('amount_paid'),
        ];

        $todayCost = Cost::whereDate('cost_date', Carbon::today())
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('entries')
            ->get()
            ->sum(fn($cost) => $cost->entries->sum('amount'));

        $pendingApprovals = 0;
        if ($user->isAdmin()) {
            $pendingApprovals = PaymentTransaction::where('payment_type', 'discounted')
                ->where('is_approved', false)
                ->when($branchId, $transactionConstraint)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'students'          => $studentStats,
                'invoices'          => $invoiceStats,
                'collections'       => $collectionStats,
                'today_cost'        => $todayCost,
                'pending_approvals' => $pendingApprovals,
            ],
        ]);
    }

    /**
     * Helper: Resolve branch ID based on user role and request
     */
    private function resolveBranchId(Request $request, $user): ?int
    {
        if ($user->isAdmin()) {
            return $request->get('branch_id') ? (int) $request->get('branch_id') : null;
        }

        return $user->branch_id ?: null;
    }
}