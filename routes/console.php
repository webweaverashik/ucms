<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate-monthly')
    ->monthly() // Run on 1st day of month at 12:30 AM
    ->appendOutputTo(storage_path('logs/invoice-generation.log'));


Schedule::command('sms:send-birthday-wish')
    ->daily('10:00');