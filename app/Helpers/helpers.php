<?php

use App\Models\SMS\SmsTemplate;
use App\Services\AutoSmsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Clears U-CMS (branch-based) cache.
 */
/**
 * Clear dashboard cache when clearing UCMS caches
 */
if (! function_exists('clearUCMSCaches')) {
    function clearUCMSCaches(): void
    {
        if (! auth()->check()) {
            return;
        }

        $branchId = auth()->user()->branch_id;

        // Existing cache clearing...
        Cache::forget('students_list_branch_' . $branchId);
        Cache::forget('alumni_students_list_branch_' . $branchId);
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

/**
 * Convert date to Bengali numbers in DD-MM-YYYY format
 */
function ashikBnNumericDate($date)
{
    if (! $date) {
        return '-';
    }

    // If $date is optional, unwrap it
    if ($date instanceof \Illuminate\Support\Optional) {
        $date = $date->__get('wrapped') ?? null; // or just $date = $date->get(); if you wrap manually
    }

    // If $date is Carbon, it's already DateTime compatible
    if (! ($date instanceof \DateTime)) {
        $date = new \DateTime($date);
    }

    $day   = ashikBnNum($date->format('d'));
    $month = ashikBnNum($date->format('m'));
    $year  = ashikBnNum($date->format('Y'));

    return "$day-$month-$year";
}

// Reuse this for number conversion
function ashikBnNum($numberOrText)
{
    $bnNumbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    // Replace all digits 0-9 with Bengali equivalents
    return str_replace(range(0, 9), $bnNumbers, $numberOrText);
}

function ashikBatchBn($word)
{
    $map = [
        'Orun'     => 'অরুণ',
        'Usha'     => 'ঊষা',
        'Proloy'   => 'প্রলয়',
        'Dhumketu' => 'ধূমকেতু',
    ];

    return $map[$word] ?? $word; // fallback to original if not found
}
