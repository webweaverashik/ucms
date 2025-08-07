<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

/**
 * Clears U-CMS (branch-based) cache.
 */
if (! function_exists('clearUCMSCaches')) {
    function clearUCMSCaches(): void
    {
        if (! auth()->check()) {
            return;
        }

        $branchId = auth()->user()->branch_id;

        Cache::forget('students_list_branch_' . $branchId);
        Cache::forget('guardians_list_branch_' . $branchId);
        Cache::forget('invoices_index_branch_' . $branchId);
    }
}

/**
 * Clears Laravel system-level caches (config, route, view, event).
 */
if (! function_exists('clearServerCache')) {
    function clearServerCache(): void
    {
        Artisan::call('optimize:clear');
    }
}
