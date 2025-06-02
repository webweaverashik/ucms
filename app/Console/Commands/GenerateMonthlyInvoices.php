<?php
namespace App\Console\Commands;

use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyInvoices extends Command
{
    protected $signature   = 'invoices:generate-monthly';
    protected $description = 'Generate monthly tuition fee invoices for active students';

    public function handle()
    {
        $currentMonth = Carbon::now()->format('m');
        $currentYear  = Carbon::now()->format('Y');
        $lastMonth    = Carbon::now()->subMonth()->format('m');

        $students = Student::with(['studentActivation', 'payments', 'paymentInvoices', 'branch'])
            ->whereHas('studentActivation', function ($query) {
                $query->where('active_status', 'active');
            })
            ->whereHas('payments', function ($query) {
                $query->where('tuition_fee', '>', 0);
            })
            ->get();

        $generatedInvoices = 0;

        foreach ($students as $student) {
            $branchPrefix  = $student->branch->branch_prefix;
            $invoicePrefix = strtoupper($branchPrefix) . substr($currentYear, -2) . $currentMonth . '_';

            $lastInvoice = PaymentInvoice::where('invoice_number', 'like', $invoicePrefix . '%')
                ->orderBy('invoice_number', 'desc')
                ->first();

            $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strlen($invoicePrefix)) + 1 : 1001;

            $monthYear = $student->payments->payment_style === 'current' ? "{$currentMonth}_{$currentYear}" : "{$lastMonth}_{$currentYear}";

            $existingInvoice = $student->paymentInvoices()
                ->where('month_year', $monthYear)
                ->where('invoice_type', 'tuition_fee')
                ->exists();

            if ($existingInvoice) {
                continue;
            }

            PaymentInvoice::create([
                'invoice_number' => $invoicePrefix . $sequence,
                'student_id'     => $student->id,
                'total_amount'   => $student->payments->tuition_fee,
                'amount_due'     => $student->payments->tuition_fee,
                'month_year'     => $monthYear,
                // 'created_by'     => NULL, // system generated invoice
            ]);

            $sequence++;
            $generatedInvoices++;
        }

        $this->info("[" . now() . "] Generated {$generatedInvoices} new invoices.");
    }
}
