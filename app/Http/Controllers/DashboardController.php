<?php
namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardCacheService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected DashboardCacheService $cacheService;

    public function __construct(DashboardService $dashboardService, DashboardCacheService $cacheService)
    {
        $this->dashboardService = $dashboardService;
        $this->cacheService     = $cacheService;
    }

    /**
     * Display the dashboard based on user role
     */
    public function index()
    {
        $user = auth()->user();

        foreach (['admin', 'manager', 'accountant'] as $role) {
            if ($user->hasRole($role)) {
                // Get initial dashboard data for the view
                $branchId = in_array($role, ['admin']) ? null : $user->branch_id;
                $data     = $this->getDashboardDataCached($branchId);

                return view("dashboard.{$role}.index", compact('data'));
                // return redirect()->route("students.index");  
            }
        }

        abort(403, 'Unauthorized access');
    }

    /**
     * API: Get dashboard stats
     */
    public function getStats(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);

        $stats = $this->cacheService->getStats($branchId, function () use ($branchId) {
            return $this->dashboardService->setBranchId($branchId)->getStats();
        });

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * API: Get monthly payment chart data
     */
    public function getMonthlyPayments(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $months   = $request->input('months', 6);

        $data = $this->cacheService->getMonthlyPayments($branchId, $months, function () use ($branchId, $months) {
            return $this->dashboardService->setBranchId($branchId)->getMonthlyPaymentData($months);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get student distribution chart data
     */
    public function getStudentDistribution(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);

        $data = $this->cacheService->getStudentDistribution($branchId, function () use ($branchId) {
            return $this->dashboardService->setBranchId($branchId)->getStudentDistribution();
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get attendance analytics
     */
    public function getAttendanceAnalytics(Request $request): JsonResponse
    {
        $branchId  = $this->resolveBranchId($request);
        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));

        $data = $this->cacheService->getAttendanceAnalytics($branchId, $startDate, $endDate, function () use ($branchId, $startDate, $endDate) {
            return $this->dashboardService->setBranchId($branchId)->getAttendanceAnalytics($startDate, $endDate);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get invoice status breakdown
     */
    public function getInvoiceStatus(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);

        $data = $this->cacheService->getInvoiceStatus($branchId, function () use ($branchId) {
            return $this->dashboardService->setBranchId($branchId)->getInvoiceStatusBreakdown();
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get recent transactions
     */
    public function getRecentTransactions(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $limit    = $request->input('limit', 10);

        $data = $this->cacheService->getRecentTransactions($branchId, $limit, function () use ($branchId, $limit) {
            return $this->dashboardService->setBranchId($branchId)->getRecentTransactions($limit);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get top employees
     */
    public function getTopEmployees(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $limit    = $request->input('limit', 5);

        $data = $this->cacheService->getTopEmployees($branchId, $limit, function () use ($branchId, $limit) {
            return $this->dashboardService->setBranchId($branchId)->getTopEmployees($limit);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get top subjects
     */
    public function getTopSubjects(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $limit    = $request->input('limit', 5);

        $data = $this->cacheService->getTopSubjects($branchId, $limit, function () use ($branchId, $limit) {
            return $this->dashboardService->setBranchId($branchId)->getTopSubjects($limit);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get login activities
     */
    public function getLoginActivities(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $limit    = $request->input('limit', 10);

        $data = $this->cacheService->getLoginActivities($branchId, $limit, function () use ($branchId, $limit) {
            return $this->dashboardService->setBranchId($branchId)->getLoginActivities($limit);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get batch stats
     */
    public function getBatchStats(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);

        $data = $this->cacheService->getBatchStats($branchId, function () use ($branchId) {
            return $this->dashboardService->setBranchId($branchId)->getBatchStats();
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Get all dashboard data at once
     */
    public function getAllData(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $this->dashboardService->setBranchId($branchId);

        $data = [
            'stats'                => $this->cacheService->getStats($branchId, fn() => $this->dashboardService->getStats()),
            'monthly_payments'     => $this->cacheService->getMonthlyPayments($branchId, 6, fn() => $this->dashboardService->getMonthlyPaymentData(6)),
            'student_distribution' => $this->cacheService->getStudentDistribution($branchId, fn() => $this->dashboardService->getStudentDistribution()),
            'invoice_status'       => $this->cacheService->getInvoiceStatus($branchId, fn() => $this->dashboardService->getInvoiceStatusBreakdown()),
            'recent_transactions'  => $this->cacheService->getRecentTransactions($branchId, 10, fn() => $this->dashboardService->getRecentTransactions(10)),
            'top_employees'        => $this->cacheService->getTopEmployees($branchId, 5, fn() => $this->dashboardService->getTopEmployees(5)),
            'top_subjects'         => $this->cacheService->getTopSubjects($branchId, 5, fn() => $this->dashboardService->getTopSubjects(5)),
            'batch_stats'          => $this->cacheService->getBatchStats($branchId, fn() => $this->dashboardService->getBatchStats()),
        ];

        // Only include login activities for admin
        if (auth()->user()->hasRole('admin')) {
            $data['login_activities'] = $this->cacheService->getLoginActivities($branchId, 10, fn() => $this->dashboardService->getLoginActivities(10));
        }

        return response()->json([
            'success'   => true,
            'data'      => $data,
            'branch_id' => $branchId,
        ]);
    }

    /**
     * API: Clear dashboard cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');

        if ($branchId === 'all' || $branchId === null) {
            $this->cacheService->clearAllCache();
        } else {
            $this->cacheService->clearBranchCache((int) $branchId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache cleared successfully',
        ]);
    }

    /**
     * Get dashboard data with caching for initial page load
     */
    protected function getDashboardDataCached(?int $branchId): array
    {
        $this->dashboardService->setBranchId($branchId);

        return [
            'stats'          => $this->cacheService->getStats($branchId, fn() => $this->dashboardService->getStats()),
            'branches'       => $this->dashboardService->getBranches(),
            'current_branch' => $this->dashboardService->getCurrentBranch(),
            'user'           => [
                'name'      => auth()->user()->name,
                'email'     => auth()->user()->email,
                'role'      => auth()->user()->roles->first()?->name ?? 'user',
                'branch_id' => auth()->user()->branch_id,
            ],
        ];
    }

    /**
     * Resolve branch ID from request based on user role
     */
    protected function resolveBranchId(Request $request): ?int
    {
        $user = auth()->user();

        // Admin can view any branch or all branches
        if ($user->hasRole('admin')) {
            $branchId = $request->input('branch_id');

            // 'all' or null means all branches
            if ($branchId === 'all' || $branchId === null || $branchId === '') {
                return null;
            }

            return (int) $branchId;
        }

        // Non-admin users can only view their assigned branch
        return $user->branch_id;
    }
}
