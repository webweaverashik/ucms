<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    protected int $ttl = 300;

    /**
     * Cache key prefixes
     */
    protected const PREFIX = 'dashboard_';
    protected const STATS = 'stats_';
    protected const CHARTS = 'charts_';
    protected const TABLES = 'tables_';

    /**
     * Get cached dashboard stats or compute
     */
    public function getStats(?int $branchId, callable $callback): array
    {
        $key = $this->getStatsKey($branchId);

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached monthly payment data
     */
    public function getMonthlyPayments(?int $branchId, int $months, callable $callback): array
    {
        $key = $this->getChartKey('monthly_payments', $branchId) . "_m{$months}";

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached student distribution
     */
    public function getStudentDistribution(?int $branchId, callable $callback): array
    {
        $key = $this->getChartKey('student_distribution', $branchId);

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached attendance analytics
     */
    public function getAttendanceAnalytics(?int $branchId, string $startDate, string $endDate, callable $callback): array
    {
        $key = $this->getChartKey('attendance', $branchId) . "_{$startDate}_{$endDate}";

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached invoice status breakdown
     */
    public function getInvoiceStatus(?int $branchId, callable $callback): array
    {
        $key = $this->getChartKey('invoice_status', $branchId);

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached recent transactions
     */
    public function getRecentTransactions(?int $branchId, int $limit, callable $callback): array
    {
        $key = $this->getTableKey('transactions', $branchId) . "_l{$limit}";

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached top employees
     */
    public function getTopEmployees(?int $branchId, int $limit, callable $callback): array
    {
        $key = $this->getTableKey('top_employees', $branchId) . "_l{$limit}";

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached top subjects
     */
    public function getTopSubjects(?int $branchId, int $limit, callable $callback): array
    {
        $key = $this->getTableKey('top_subjects', $branchId) . "_l{$limit}";

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached login activities
     */
    public function getLoginActivities(?int $branchId, int $limit, callable $callback): array
    {
        $key = $this->getTableKey('login_activities', $branchId) . "_l{$limit}";

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Get cached batch stats
     */
    public function getBatchStats(?int $branchId, callable $callback): array
    {
        $key = $this->getTableKey('batch_stats', $branchId);

        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Clear all dashboard cache for a branch
     */
    public function clearBranchCache(?int $branchId = null): void
    {
        $patterns = [
            $this->getStatsKey($branchId),
            $this->getChartKey('*', $branchId),
            $this->getTableKey('*', $branchId),
        ];

        // Clear specific branch cache
        if ($branchId) {
            $this->clearCacheByPattern(self::PREFIX . "*_branch_{$branchId}*");
        }

        // Always clear "all branches" cache
        $this->clearCacheByPattern(self::PREFIX . "*_branch_all*");
    }

    /**
     * Clear all dashboard cache
     */
    public function clearAllCache(): void
    {
        $this->clearCacheByPattern(self::PREFIX . '*');
    }

    /**
     * Clear stats cache
     */
    public function clearStatsCache(?int $branchId = null): void
    {
        Cache::forget($this->getStatsKey($branchId));
        Cache::forget($this->getStatsKey(null)); // Also clear "all" cache
    }

    /**
     * Clear payment/transaction related cache
     */
    public function clearPaymentCache(?int $branchId = null): void
    {
        $keys = [
            $this->getChartKey('monthly_payments', $branchId),
            $this->getChartKey('invoice_status', $branchId),
            $this->getTableKey('transactions', $branchId),
            $this->getTableKey('top_employees', $branchId),
            $this->getStatsKey($branchId),
        ];

        foreach ($keys as $key) {
            $this->clearCacheByPattern($key . '*');
        }

        // Clear "all" cache too
        if ($branchId) {
            $this->clearPaymentCache(null);
        }
    }

    /**
     * Clear student related cache
     */
    public function clearStudentCache(?int $branchId = null): void
    {
        $keys = [
            $this->getStatsKey($branchId),
            $this->getChartKey('student_distribution', $branchId),
            $this->getTableKey('batch_stats', $branchId),
            $this->getTableKey('top_subjects', $branchId),
        ];

        foreach ($keys as $key) {
            $this->clearCacheByPattern($key . '*');
        }

        // Clear "all" cache too
        if ($branchId) {
            $this->clearStudentCache(null);
        }
    }

    /**
     * Clear attendance cache
     */
    public function clearAttendanceCache(?int $branchId = null): void
    {
        $this->clearCacheByPattern($this->getChartKey('attendance', $branchId) . '*');

        if ($branchId) {
            $this->clearAttendanceCache(null);
        }
    }

    /**
     * Clear login activity cache
     */
    public function clearLoginActivityCache(): void
    {
        $this->clearCacheByPattern(self::PREFIX . self::TABLES . 'login_activities*');
    }

    /**
     * Generate stats cache key
     */
    protected function getStatsKey(?int $branchId): string
    {
        $branchSuffix = $branchId ? "branch_{$branchId}" : 'branch_all';
        return self::PREFIX . self::STATS . $branchSuffix;
    }

    /**
     * Generate chart cache key
     */
    protected function getChartKey(string $chart, ?int $branchId): string
    {
        $branchSuffix = $branchId ? "branch_{$branchId}" : 'branch_all';
        return self::PREFIX . self::CHARTS . "{$chart}_{$branchSuffix}";
    }

    /**
     * Generate table cache key
     */
    protected function getTableKey(string $table, ?int $branchId): string
    {
        $branchSuffix = $branchId ? "branch_{$branchId}" : 'branch_all';
        return self::PREFIX . self::TABLES . "{$table}_{$branchSuffix}";
    }

    /**
     * Clear cache by pattern
     * Note: This works with Redis/Memcached. For file cache, use tags or manual deletion.
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        $driver = config('cache.default');

        if ($driver === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        } elseif ($driver === 'file') {
            // For file driver, we need to manually track keys or use Cache::flush()
            // Here we'll just forget specific known keys
            Cache::forget(str_replace('*', '', $pattern));
        }
    }

    /**
     * Set custom TTL
     */
    public function setTtl(int $seconds): self
    {
        $this->ttl = $seconds;
        return $this;
    }
}
