<?php
namespace App\Services;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Cost\Cost;
use App\Models\Cost\CostEntry;
use App\Models\Cost\CostType;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use App\Models\Student\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Service
 *
 * This service provides reusable methods for dashboard statistics.
 * Can be used if you prefer to keep business logic separate from controllers.
 */
class DashboardService
{
    /**
     * Get student statistics
     */
    public function getStudentStats(?int $branchId = null): array
    {
        $query = Student::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total'         => (clone $query)->count(),
            'active'        => (clone $query)->active()->count(),
            'pending'       => (clone $query)->pending()->count(),
            'inactive'      => (clone $query)
                ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'inactive'))
                ->count(),
            'new_this_week' => Student::query()
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count(),
        ];
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStats(?int $branchId = null): array
    {
        $studentQuery = function ($query) use ($branchId) {
            $query->withoutTrashed();
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        };

        $dueInvoices = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', $studentQuery)
            ->get();

        $topDueStudents = PaymentInvoice::select('student_id', DB::raw('SUM(amount_due) as total_due'))
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
                'total_due'         => $item->total_due,
            ]);

        return [
            'total_due_count'  => $dueInvoices->count(),
            'total_due_amount' => $dueInvoices->sum('amount_due'),
            'top_due_students' => $topDueStudents,
        ];
    }

    /**
     * Get collection statistics
     */
    public function getCollectionStats(?int $branchId = null): array
    {
        $transactionConstraint = function ($q) use ($branchId) {
            if ($branchId) {
                $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId));
            }
        };

        $totalCollection = PaymentTransaction::where('is_approved', true)
            ->when($branchId, $transactionConstraint)
            ->sum('amount_paid');

        $todayCollection = PaymentTransaction::where('is_approved', true)
            ->whereDate('created_at', Carbon::today())
            ->when($branchId, $transactionConstraint)
            ->sum('amount_paid');

        $monthCollection = PaymentTransaction::where('is_approved', true)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->when($branchId, $transactionConstraint)
            ->sum('amount_paid');

        $hourlyCollection = PaymentTransaction::where('is_approved', true)
            ->whereDate('created_at', Carbon::today())
            ->when($branchId, $transactionConstraint)
            ->selectRaw('HOUR(created_at) as hour, SUM(amount_paid) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('total', 'hour')
            ->toArray();

        $hourlyData = [];
        for ($i = 8; $i <= 22; $i++) {
            $hourlyData[] = [
                'hour'   => sprintf('%02d:00', $i),
                'amount' => $hourlyCollection[$i] ?? 0,
            ];
        }

        $userWiseCollection = PaymentTransaction::with('createdBy:id,name')
            ->where('is_approved', true)
            ->whereDate('created_at', Carbon::today())
            ->when($branchId, $transactionConstraint)
            ->selectRaw('created_by, SUM(amount_paid) as total')
            ->groupBy('created_by')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'user_name' => $item->createdBy?->name ?? 'System',
                'total'     => $item->total,
            ])
            ->toArray();

        return [
            'total_collection'     => $totalCollection,
            'today_collection'     => $todayCollection,
            'month_collection'     => $monthCollection,
            'hourly_collection'    => $hourlyData,
            'user_wise_collection' => $userWiseCollection,
        ];
    }

    /**
     * Get cost statistics
     */
    public function getCostStats(?int $branchId = null, string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year'  => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $endDate = Carbon::now();

        $costs = Cost::with(['entries.costType', 'branch:id,branch_name'])
            ->whereBetween('cost_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

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
            ])
            ->toArray();

        $costTypes = CostType::active()->orderBy('name')->get(['id', 'name']);

        return [
            'total_cost'          => $totalCost,
            'cost_type_breakdown' => $costTypeBreakdown,
            'cost_types'          => $costTypes,
            'period'              => $period,
            'start_date'          => $startDate->format('d M Y'),
            'end_date'            => $endDate->format('d M Y'),
        ];
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats(?int $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $end   = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $attendanceByClass = StudentAttendance::whereBetween('attendance_date', [$start, $end])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('classname:id,name,class_numeral')
            ->selectRaw('class_id, status, COUNT(*) as count')
            ->groupBy('class_id', 'status')
            ->get();

        $classData = [];
        foreach ($attendanceByClass as $item) {
            $classId = $item->class_id;
            if (! isset($classData[$classId])) {
                $classData[$classId] = [
                    'class_id'      => $classId,
                    'class_name'    => $item->classname?->name ?? 'Unknown',
                    'class_numeral' => $item->classname?->class_numeral ?? '',
                    'present'       => 0,
                    'absent'        => 0,
                    'late'          => 0,
                    'leave'         => 0,
                ];
            }
            $classData[$classId][$item->status] = $item->count;
        }

        $attendanceByBatch = StudentAttendance::whereBetween('attendance_date', [$start, $end])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('batch:id,name')
            ->selectRaw('batch_id, status, COUNT(*) as count')
            ->groupBy('batch_id', 'status')
            ->get();

        $batchData = [];
        foreach ($attendanceByBatch as $item) {
            $batchId = $item->batch_id;
            if (! isset($batchData[$batchId])) {
                $batchData[$batchId] = [
                    'batch_id'   => $batchId,
                    'batch_name' => $item->batch?->name ?? 'Unknown',
                    'present'    => 0,
                    'absent'     => 0,
                    'late'       => 0,
                    'leave'      => 0,
                ];
            }
            $batchData[$batchId][$item->status] = $item->count;
        }

        $todayAttendance = StudentAttendance::whereDate('attendance_date', Carbon::today())
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'class_wise' => array_values($classData),
            'batch_wise' => array_values($batchData),
            'today'      => [
                'present' => $todayAttendance['present'] ?? 0,
                'absent'  => $todayAttendance['absent'] ?? 0,
                'late'    => $todayAttendance['late'] ?? 0,
                'leave'   => $todayAttendance['leave'] ?? 0,
            ],
            'date_range' => [
                'start' => $start->format('Y-m-d'),
                'end'   => $end->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get dashboard summary
     */
    public function getSummary(?int $branchId = null, bool $isAdmin = false): array
    {
        $studentStats = $this->getStudentStats($branchId);

        $studentConstraint = function ($query) use ($branchId) {
            $query->withoutTrashed();
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        };

        $dueInvoices = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', $studentConstraint);

        $transactionConstraint = function ($q) use ($branchId) {
            if ($branchId) {
                $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId));
            }
        };

        $todayCollection = PaymentTransaction::where('is_approved', true)
            ->whereDate('created_at', Carbon::today())
            ->when($branchId, $transactionConstraint)
            ->sum('amount_paid');

        $monthCollection = PaymentTransaction::where('is_approved', true)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->when($branchId, $transactionConstraint)
            ->sum('amount_paid');

        $todayCost = Cost::whereDate('cost_date', Carbon::today())
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('entries')
            ->get()
            ->sum(fn($cost) => $cost->entries->sum('amount'));

        $pendingApprovals = 0;
        if ($isAdmin) {
            $pendingApprovals = PaymentTransaction::where('payment_type', 'discounted')
                ->where('is_approved', false)
                ->when($branchId, $transactionConstraint)
                ->count();
        }

        return [
            'students'          => [
                'total'   => $studentStats['total'],
                'active'  => $studentStats['active'],
                'pending' => $studentStats['pending'],
            ],
            'invoices'          => [
                'due_count'  => $dueInvoices->count(),
                'due_amount' => $dueInvoices->sum('amount_due'),
            ],
            'collections'       => [
                'today' => $todayCollection,
                'month' => $monthCollection,
            ],
            'today_cost'        => $todayCost,
            'pending_approvals' => $pendingApprovals,
        ];
    }
}
