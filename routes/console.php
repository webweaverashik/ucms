<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate-monthly')->monthlyOn(1, '00:30')->appendOutputTo(storage_path('logs/invoice-generation.log'));
Schedule::command('sms:send-birthday-wish')->dailyAt('10:00');
Schedule::command('sms:send-due-invoice-reminder')->dailyAt('10:00');

// Run cleanup for old database backups at 00:30 (Spatie managed)
Schedule::command('backup:clean')->daily()->at('23:49');

// Run cleanup for old custom file backups at 00:45 (keep 7 days)
Schedule::command('backup:clean-files --days=7')
    ->daily()
    ->at('23:48')
    ->appendOutputTo(storage_path('logs/backup-files-cleanup.log'));

// Run daily database backup at 01:00
Schedule::command('backup:run --only-db')->daily()->at('01:00');
// Schedule::command('sms:send-overdue-invoice-reminder')->dailyAt('10:00');
