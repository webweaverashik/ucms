<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendOverdueInvoiceReminderSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-overdue-invoice-reminder-sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
