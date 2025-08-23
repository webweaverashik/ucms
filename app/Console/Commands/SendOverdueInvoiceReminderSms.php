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
    protected $signature = 'sms:send-overdue-invoice-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS to students with overdue invoices i.e. due date passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
