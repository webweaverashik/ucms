<?php

use App\Models\SMS\SmsTemplate;
use App\Services\AutoSmsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

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
        Cache::forget('transactions_branch_' . $branchId);
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

/**
 * Send Auto SMS by template
 */
if (! function_exists('send_auto_sms')) {
    /**
     * Send Auto SMS if template is active
     *
     * @param string $templateTitle
     * @param string $mobile
     * @param array $data
     * @param string $messageType
     * @param int|null $userId
     * @return mixed
     */
    function send_auto_sms(string $templateTitle, string $mobile, array $data = [], string $messageType = 'TEXT', ?int $userId = null)
    {
        $template = SmsTemplate::where('name', $templateTitle)->first();

        if (! $template || ! $template->is_active) {
            return ['skipped' => true, 'message' => "SMS template '{$templateTitle}' is inactive or not found."];
        }

        $autoSmsService = app(AutoSmsService::class);

        return $autoSmsService->sendAutoSms($templateTitle, $mobile, $data, $messageType, $userId);
    }
}
