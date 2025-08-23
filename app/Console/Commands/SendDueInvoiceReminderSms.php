<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDueInvoiceReminderSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send-due-invoice-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS to students with whose invoices are not paid and today is due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
