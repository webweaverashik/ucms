<?php
namespace App\Console\Commands;

use App\Models\Payment\PaymentInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        $today = Carbon::today();

        $dueInvoices = PaymentInvoice::with(['student.mobileNumbers', 'student.studentActivation', 'student.payments'])
            ->where('status', '!=', 'paid')
            ->where('invoice_type', 'tuition_fee')
            ->whereHas('student', fn($q) => $q->whereHas('studentActivation', fn($q2) => $q2->where('active_status', 'active')))
            ->get()
            ->filter(function ($invoice) use ($today) {
                $payment = $invoice->student->payments;
                if (! $payment) {
                    return false;
                }

                // Construct this month's due date
                $dueDateThisMonth = Carbon::createFromDate($today->year, $today->month, $payment->due_date);

                // Check if today is 2 days before
                return $today->isSameDay($dueDateThisMonth->subDays(2));
            });

        Log::info('Found due invoices count: ' . $dueInvoices->count());

        foreach ($dueInvoices as $invoice) {
            $mobile = $invoice->student->mobileNumbers->where('number_type', 'sms')->first()->mobile_number ?? null;

            Log::info("Processing invoice {$invoice->id} for student {$invoice->student->id}, mobile: {$mobile}");

            if ($mobile) {
                send_auto_sms('student_due_invoice_reminder', $mobile, [
                    'student_name' => $invoice->student->name,
                    'month_year'   => $invoice->month_year
                    ? Carbon::createFromDate(
                        explode('_', $invoice->month_year)[1], // year
                        explode('_', $invoice->month_year)[0], // month
                    )->format('F')
                    : now()->format('F'),
                    'due_amount'   => $invoice->amount_due,
                    'due_date'     => $this->ordinal($invoice->student->payments->due_date) . ' ' . now()->format('F'),
                ]);

                Log::info("SMS sent for invoice {$invoice->id} to {$mobile}");
            } else {
                Log::warning("No mobile number found for student {$invoice->student->id}");
            }
        }

        $this->info('Due invoice reminder SMS processed.');
    }


    /**
     * Convert number to ordinal (1st, 2nd, 3rd, etc.)
     */
    private function ordinal(int $number): string
    {
        if (! in_array($number % 100, [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }
        return $number . 'th';
    }
}
