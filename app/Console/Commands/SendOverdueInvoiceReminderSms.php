<?php
namespace App\Console\Commands;

use App\Models\Payment\PaymentInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendOverdueInvoiceReminderSms extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:send-overdue-invoice-reminder';

    /**
     * The console command description.
     */
    protected $description = 'Send SMS to students with overdue tuition fee invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $dueInvoices = PaymentInvoice::with(['student.mobileNumbers', 'student.studentActivation', 'student.payments'])
            ->where('status', '!=', 'paid')
            ->whereHas('invoiceType', fn($q) => $q->where('type_name', 'tuition_fee'))
            ->whereHas('student.studentActivation', fn($q) => $q->where('active_status', 'active'))
            ->whereHas('student.payments', fn($q) => $q->whereDate('due_date', '<', $today))
            ->get();

        Log::info('Overdue invoice reminder: found ' . $dueInvoices->count() . ' invoices');

        foreach ($dueInvoices as $invoice) {
            $student = $invoice->student;

            if (! $student) {
                continue;
            }

            $mobile = $student->mobileNumbers->where('number_type', 'sms')->first()?->mobile_number;

            if (! $mobile) {
                Log::warning("No SMS number found for student ID {$student->id}");
                continue;
            }

            $dueDate = $student->payments?->due_date;

            $formattedDueDate = $dueDate ? $this->ordinal(Carbon::parse($dueDate)->day) . ' ' . Carbon::parse($dueDate)->format('F') : '';

            $invoiceMonth = $invoice->month_year
                ? Carbon::createFromDate(
                explode('_', $invoice->month_year)[1], // year
                explode('_', $invoice->month_year)[0], // month
            )->format('F')
                : now()->format('F');

            send_auto_sms('student_overdue_invoice_reminder', $mobile, [
                'student_name' => $student->name,
                'month_year'   => $invoiceMonth,
                'due_date'     => $formattedDueDate,
                'due_amount'   => $invoice->amount_due,
            ]);

            Log::info("Overdue invoice SMS sent | Invoice ID: {$invoice->id} | Student ID: {$student->id}");
        }

        $this->info('Overdue invoice reminder SMS processing completed.');
    }

    /**
     * Convert number to ordinal (1st, 2nd, 3rd, etc.)
     */
    private function ordinal(int $number): string
    {
        if (! in_array($number % 100, [11, 12, 13])) {
            return match ($number % 10) {
                1       => $number . 'st',
                2       => $number . 'nd',
                3       => $number . 'rd',
                default => $number . 'th',
            };
        }

        return $number . 'th';
    }
}
