<?php

use App\Services\Dashboard\DashboardCacheService;

/*
|--------------------------------------------------------------------------
| Dashboard Cache Helper Functions
|--------------------------------------------------------------------------
|
| Add to composer.json autoload:
| "files": [
|     "app/Helpers/helpers.php",
|     "app/Helpers/dashboard_helpers.php"
| ]
|
| Then run: composer dump-autoload
|
*/

if (! function_exists('clearDashboardCache')) {
    /**
     * Clear all dashboard cache
     */
    function clearDashboardCache(): void
    {
        app(DashboardCacheService::class)->clearAllCache();
    }
}

if (! function_exists('clearDashboardCacheForBranch')) {
    /**
     * Clear dashboard cache for a specific branch
     */
    function clearDashboardCacheForBranch(?int $branchId): void
    {
        app(DashboardCacheService::class)->clearBranchCache($branchId);
    }
}

if (! function_exists('clearDashboardStatsCache')) {
    /**
     * Clear dashboard stats cache
     */
    function clearDashboardStatsCache(?int $branchId = null): void
    {
        app(DashboardCacheService::class)->clearStatsCache($branchId);
    }
}

if (! function_exists('clearDashboardPaymentCache')) {
    /**
     * Clear dashboard payment-related cache
     * Call this when payments, invoices, or transactions change
     */
    function clearDashboardPaymentCache(?int $branchId = null): void
    {
        app(DashboardCacheService::class)->clearPaymentCache($branchId);
    }
}

if (! function_exists('clearDashboardStudentCache')) {
    /**
     * Clear dashboard student-related cache
     * Call this when students are added, updated, or status changes
     */
    function clearDashboardStudentCache(?int $branchId = null): void
    {
        app(DashboardCacheService::class)->clearStudentCache($branchId);
    }
}

if (! function_exists('clearDashboardAttendanceCache')) {
    /**
     * Clear dashboard attendance cache
     * Call this when attendance records are added or updated
     */
    function clearDashboardAttendanceCache(?int $branchId = null): void
    {
        app(DashboardCacheService::class)->clearAttendanceCache($branchId);
    }
}

if (! function_exists('clearDashboardLoginCache')) {
    /**
     * Clear dashboard login activity cache
     * Call this when new login activities are recorded
     */
    function clearDashboardLoginCache(): void
    {
        app(DashboardCacheService::class)->clearLoginActivityCache();
    }
}

if (! function_exists('formatTakaCurrency')) {
    /**
     * Format amount as Taka currency
     */
    function formatTakaCurrency(int | float $amount, bool $short = false): string
    {
        if ($short) {
            if ($amount >= 10000000) {
                return '৳' . number_format($amount / 10000000, 2) . ' Cr';
            }
            if ($amount >= 100000) {
                return '৳' . number_format($amount / 100000, 2) . ' L';
            }
            if ($amount >= 1000) {
                return '৳' . number_format($amount / 1000, 1) . 'K';
            }
        }

        return '৳' . number_format($amount);
    }
}

if (! function_exists('getInvoiceTypeBadgeClass')) {
    /**
     * Get badge class for invoice type
     */
    function getInvoiceTypeBadgeClass(string $type): string
    {
        return match ($type) {
            'tuition_fee'    => 'badge-light-primary',
            'admission_fee'  => 'badge-light-success',
            'sheet_fee'      => 'badge-light-info',
            'exam_fee'       => 'badge-light-warning',
            'model_test_fee' => 'badge-light-dark',
            default          => 'badge-light-secondary',
        };
    }
}

if (! function_exists('getPaymentStatusBadgeClass')) {
    /**
     * Get badge class for payment status
     */
    function getPaymentStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'paid'  => 'badge-light-success',
            'partial', 'partially_paid' => 'badge-light-warning',
            'due', 'pending'            => 'badge-light-danger',
            default => 'badge-light-secondary',
        };
    }
}

if (! function_exists('getUserRoleBadgeClass')) {
    /**
     * Get badge class for user role
     */
    function getUserRoleBadgeClass(string $role): string
    {
        return match ($role) {
            'admin'      => 'badge-light-danger',
            'manager'    => 'badge-light-primary',
            'accountant' => 'badge-light-info',
            'teacher'    => 'badge-light-success',
            'student'    => 'badge-light-warning',
            'guardian'   => 'badge-light-dark',
            default      => 'badge-light-secondary',
        };
    }
}
