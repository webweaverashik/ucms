<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate-monthly')->monthly()->appendOutputTo(storage_path('logs/invoice-generation.log'));
Schedule::command('sms:send-birthday-wish')->dailyAt('10:00');
Schedule::command('sms:send-student-due-invoice-reminder')->dailyAt('10:00');
Schedule::command('sms:send-student-overdue-invoice-reminder')->dailyAt('10:00');
