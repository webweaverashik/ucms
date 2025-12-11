<?php
namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use App\Models\Student\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Payment\PaymentInvoice;

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
            ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
            ->whereHas('payments', fn($q) => $q->where('tuition_fee', '>', 0))
            ->whereHas('class', function ($q) {
                $q->withoutGlobalScope('active')->where('is_active', true);
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
        
        // clearing cache after invoice generation
        $branchId = auth()->user()->branch_id;
        Cache::forget('invoices_index_branch_' . $branchId);

        $this->info("[" . now() . "] Generated {$generatedInvoices} new invoices.");
    }
}
