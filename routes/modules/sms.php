<?php

use App\Http\Controllers\SMS\SmsCampaignController;
use App\Http\Controllers\SMS\SmsController;
use App\Http\Controllers\SMS\SmsTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SMS Routes
|--------------------------------------------------------------------------
| SMS campaigns, templates, logs
*/

// Single SMS
Route::get('sms', [SmsController::class, 'sendSingleIndex']);
Route::get('sms/send-single', [SmsController::class, 'sendSingleIndex'])->name('sms.single.index');
Route::post('sms/send-single', [SmsController::class, 'sendSingle'])->name('sms.single.send');

// Campaign Approval - Note: 'sms-campaigns.approve' (original name)
Route::post('/sms/send-campaign/{id}/approve', [SmsCampaignController::class, 'approve'])->name('sms-campaigns.approve');

// Logs & Status
Route::get('sms/logs', [SmsController::class, 'smsLog'])->name('sms.logs.index');
Route::get('sms/balance', [SmsController::class, 'checkBalance'])->name('sms.balance');
Route::get('sms/status', [SmsController::class, 'checkSmsStatus'])->name('sms.status');

// Templates
Route::get('sms/templates', [SmsTemplateController::class, 'index'])->name('sms.templates.index');
Route::prefix('sms/templates')
    ->name('sms.templates.')
    ->group(function () {
        Route::patch('{template}/toggle', [SmsTemplateController::class, 'toggleStatus'])->name('toggle');
        Route::patch('{template}/update-body', [SmsTemplateController::class, 'updateBody'])->name('updateBody');
    });

// SMS Campaign Resource - uses 'sms-campaigns' naming (original)
Route::resource('sms/send-campaign', SmsCampaignController::class);