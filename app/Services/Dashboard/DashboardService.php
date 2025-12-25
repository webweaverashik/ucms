<?php

namespace App\Services\Dashboard;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Branch;
use App\Models\LoginActivity;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use App\Models\Student\StudentAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected ?int $branchId;
    protected ?User $user;

    public function __construct()
    {
        $this->user = auth()->user();
        $this->branchId = $this->user?->branch_id;
    }

    /**
     * Set branch ID for filtering (used by admin when switching branches)
     */
    public function setBranchId(?int $branchId): self
    {
        $this->branchId = $branchId;
        return $this;
    }

    /**
     * Get all dashboard data for the current context
     */
    public function getDashboardData(?int $branchId = null): array
    {
        if ($branchId !== null) {
            $this->setBranchId($branchId);
        }

        return [
            'stats' => $this->getStats(),
            'branches' => $this->getBranches(),
            'current_branch' => $this->getCurrentBranch(),
        ];
    }

    /**
     * Get all branches (for admin tabs)
     */
    public function getBranches(): array
    {
        return Branch::select('id', 'branch_name', 'branch_prefix')
            ->get()
            ->toArray();
    }

    /**
     * Get current branch info
     */
    public function getCurrentBranch(): ?array
    {
        if (!$this->branchId) {
            return null;
        }

        return Branch::select('id', 'branch_name', 'branch_prefix')
            ->find($this->branchId)
            ?->toArray();
    }

    /**
     * Get main statistics
     */
    public function getStats(): array
    {
        $query = Student::query();

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $totalStudents = (clone $query)->count();

        $activeStudents = (clone $query)
            ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
            ->count();

        $inactiveStudents = $totalStudents - $activeStudents;

        // Pending students (no activation record)
        $pendingStudents = (clone $query)->pending()->count();

        // Invoice stats
        $invoiceStats = $this->getInvoiceStats();

        // Collection stats
        $collectionStats = $this->getCollectionStats();

        // Calculate percentages and trends
        $activePercentage = $totalStudents > 0
            ? round(($activeStudents / $totalStudents) * 100, 1)
            : 0;

        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'inactive_students' => $inactiveStudents,
            'pending_students' => $pendingStudents,
            'active_percentage' => $activePercentage,
            'due_invoices' => $invoiceStats['due'],
            'partially_paid_invoices' => $invoiceStats['partially_paid'],
            'paid_invoices' => $invoiceStats['paid'],
            'total_invoices' => $invoiceStats['total'],
            'total_collection' => $collectionStats['total'],
            'current_month_collection' => $collectionStats['current_month'],
            'previous_month_collection' => $collectionStats['previous_month'],
            'collection_trend' => $collectionStats['trend'],
        ];
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStats(): array
    {
        $query = PaymentInvoice::query();

        if ($this->branchId) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $this->branchId));
        }

        $stats = $query->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'due' => $stats['due'] ?? 0,
            'partially_paid' => $stats['partially_paid'] ?? 0,
            'paid' => $stats['paid'] ?? 0,
            'total' => array_sum($stats),
        ];
    }

    /**
     * Get collection statistics
     */
    public function getCollectionStats(): array
    {
        $query = PaymentTransaction::query()
            ->where('is_approved', true);

        if ($this->branchId) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $this->branchId));
        }

        $total = (clone $query)->sum('amount_paid');

        $currentMonth = (clone $query)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount_paid');

        $previousMonth = (clone $query)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('amount_paid');

        // Calculate trend percentage
        $trend = $previousMonth > 0
            ? round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1)
            : ($currentMonth > 0 ? 100 : 0);

        return [
            'total' => $total,
            'current_month' => $currentMonth,
            'previous_month' => $previousMonth,
            'trend' => $trend,
        ];
    }

    /**
     * Get monthly payment data for chart
     */
    public function getMonthlyPaymentData(int $months = 6): array
    {
        $data = [];
        $labels = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $query = PaymentTransaction::query()
                ->where('is_approved', true)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);

            if ($this->branchId) {
                $query->whereHas('student', fn($q) => $q->where('branch_id', $this->branchId));
            }

            $collection = $query->sum('amount_paid');

            // Get due amount for the month
            $dueQuery = PaymentInvoice::query()
                ->where('status', '!=', 'paid')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);

            if ($this->branchId) {
                $dueQuery->whereHas('student', fn($q) => $q->where('branch_id', $this->branchId));
            }

            $due = $dueQuery->sum('amount_due');

            $data[] = [
                'collection' => $collection,
                'due' => $due,
            ];
        }

        return [
            'labels' => $labels,
            'collection' => array_column($data, 'collection'),
            'due' => array_column($data, 'due'),
        ];
    }

    /**
     * Get student distribution by class
     */
    public function getStudentDistribution(): array
    {
        $classes = ClassName::withoutGlobalScope('active')
            ->where('is_active', true)
            ->orderBy('class_numeral')
            ->get();

        $labels = [];
        $activeData = [];
        $inactiveData = [];

        foreach ($classes as $class) {
            $labels[] = $class->name;

            $query = Student::where('class_id', $class->id);

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $active = (clone $query)
                ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
                ->count();

            $inactive = (clone $query)
                ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'inactive'))
                ->count();

            $activeData[] = $active;
            $inactiveData[] = $inactive;
        }

        return [
            'labels' => $labels,
            'active' => $activeData,
            'inactive' => $inactiveData,
        ];
    }

    /**
     * Get attendance analytics
     */
    public function getAttendanceAnalytics(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfWeek();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfWeek();

        $classes = ClassName::withoutGlobalScope('active')
            ->where('is_active', true)
            ->orderBy('class_numeral')
            ->get();

        $labels = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];

        foreach ($classes as $class) {
            $labels[] = $class->name;

            $query = StudentAttendance::where('class_id', $class->id)
                ->whereBetween('attendance_date', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $stats = $query->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $presentData[] = $stats['present'] ?? 0;
            $absentData[] = $stats['absent'] ?? 0;
            $lateData[] = $stats['late'] ?? 0;
        }

        return [
            'labels' => $labels,
            'present' => $presentData,
            'absent' => $absentData,
            'late' => $lateData,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(int $limit = 10): array
    {
        $query = PaymentTransaction::with([
            'student:id,name,class_id',
            'student.class:id,name',
            'paymentInvoice:id,invoice_type,status',
        ])
            ->where('is_approved', true)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($this->branchId) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $this->branchId));
        }

        return $query->get()->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'student_name' => $transaction->student->name ?? 'N/A',
                'class_name' => $transaction->student->class->name ?? $transaction->student_classname,
                'amount' => $transaction->amount_paid,
                'type' => $transaction->paymentInvoice->invoice_type ?? 'tuition_fee',
                'status' => $transaction->remaining_amount > 0 ? 'partial' : 'paid',
                'voucher_no' => $transaction->voucher_no,
                'created_at' => $transaction->created_at->format('d M Y, h:i A'),
                'created_at_human' => $transaction->created_at->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Get top employees by transactions
     */
    public function getTopEmployees(int $limit = 5): array
    {
        $query = PaymentTransaction::query()
            ->select(
                'created_by',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount_paid) as total_amount')
            )
            ->where('is_approved', true)
            ->whereNotNull('created_by')
            ->groupBy('created_by')
            ->orderByDesc('transaction_count')
            ->limit($limit);

        if ($this->branchId) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $this->branchId));
        }

        $results = $query->get();

        // Get user details
        $userIds = $results->pluck('created_by');
        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email')
            ->get()
            ->keyBy('id');

        $maxTransactions = $results->max('transaction_count') ?: 1;

        return $results->map(function ($item) use ($users, $maxTransactions) {
            $user = $users->get($item->created_by);
            return [
                'id' => $item->created_by,
                'name' => $user->name ?? 'Unknown',
                'email' => $user->email ?? '',
                'initials' => $this->getInitials($user->name ?? 'U'),
                'transaction_count' => $item->transaction_count,
                'total_amount' => $item->total_amount,
                'percentage' => round(($item->transaction_count / $maxTransactions) * 100),
            ];
        })->toArray();
    }

    /**
     * Get top enrolled subjects
     */
    public function getTopSubjects(int $limit = 5): array
    {
        $query = DB::table('subjects_taken')
            ->join('subjects', 'subjects_taken.subject_id', '=', 'subjects.id')
            ->join('students', 'subjects_taken.student_id', '=', 'students.id')
            ->select(
                'subjects.id',
                'subjects.name',
                DB::raw('COUNT(subjects_taken.id) as student_count')
            )
            ->whereNull('subjects.deleted_at')
            ->whereNull('students.deleted_at')
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc('student_count')
            ->limit($limit);

        if ($this->branchId) {
            $query->where('students.branch_id', $this->branchId);
        }

        $results = $query->get();

        $maxStudents = $results->max('student_count') ?: 1;

        return $results->map(function ($item) use ($maxStudents) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'student_count' => $item->student_count,
                'percentage' => round(($item->student_count / $maxStudents) * 100),
            ];
        })->toArray();
    }

    /**
     * Get recent login activities
     */
    public function getLoginActivities(int $limit = 10): array
    {
        $query = LoginActivity::with(['user:id,name,email'])
            ->orderByDesc('created_at')
            ->limit($limit);

        // For non-admin, filter by branch if user type supports it
        // Login activities don't have direct branch relation, 
        // but we can filter by user's branch
        if ($this->branchId && !$this->user?->hasRole('admin')) {
            $query->whereHas('user', fn($q) => $q->where('branch_id', $this->branchId));
        }

        return $query->get()->map(function ($activity) {
            $actor = $activity->actor();
            return [
                'id' => $activity->id,
                'user_name' => $actor->name ?? 'Unknown',
                'user_type' => $activity->user_type,
                'ip_address' => $activity->ip_address,
                'device' => $activity->device ?? $this->parseUserAgent($activity->user_agent),
                'created_at' => $activity->created_at->format('d M Y, h:i A'),
                'created_at_human' => $activity->created_at->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Get batch-wise student count
     */
    public function getBatchStats(): array
    {
        $query = Batch::withCount(['activeStudents']);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->get()->map(function ($batch) {
            return [
                'id' => $batch->id,
                'name' => $batch->name,
                'name_bn' => $this->getBatchNameBn($batch->name),
                'student_count' => $batch->active_students_count,
            ];
        })->toArray();
    }

    /**
     * Get invoice status breakdown for chart
     */
    public function getInvoiceStatusBreakdown(): array
    {
        $stats = $this->getInvoiceStats();

        return [
            'labels' => ['Paid', 'Partially Paid', 'Due'],
            'data' => [
                $stats['paid'],
                $stats['partially_paid'],
                $stats['due'],
            ],
            'colors' => ['#1BC5BD', '#FFA800', '#F64E60'],
        ];
    }

    /**
     * Helper: Get initials from name
     */
    protected function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials ?: 'U';
    }

    /**
     * Helper: Parse user agent string
     */
    protected function parseUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $browser = 'Unknown';
        $os = 'Unknown';

        // Detect browser
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'Edge';
        }

        // Detect OS
        if (strpos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $os = 'MacOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            $os = 'iOS';
        }

        return "{$browser} / {$os}";
    }

    /**
     * Helper: Get Bengali batch name
     */
    protected function getBatchNameBn(string $name): string
    {
        $map = [
            'Orun' => 'অরুণ',
            'Usha' => 'ঊষা',
            'Proloy' => 'প্রলয়',
            'Dhumketu' => 'ধূমকেতু',
        ];

        return $map[$name] ?? $name;
    }
}
