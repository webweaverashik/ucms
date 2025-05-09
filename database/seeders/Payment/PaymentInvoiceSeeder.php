<?php
namespace Database\Seeders\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
use Illuminate\Database\Seeder;

class PaymentInvoiceSeeder extends Seeder
{
    public function run()
    {
        $studentsWithCurrentPayment = Student::whereIn(
            'id',
            Payment::where('payment_style', 'current')->pluck('student_id')
        )->with('branch')->get();

        $yearSuffix = now()->format('y'); // '25'
        $month      = now()->format('m'); // '05'

        $branchSequences = []; // To track invoice sequence per branch

        foreach ($studentsWithCurrentPayment as $student) {
            $payment = Payment::where('student_id', $student->id)
                ->where('payment_style', 'current')
                ->inRandomOrder()
                ->first();

            if (! $payment || ! $student->branch) {
                continue;
            }

            $prefix = $student->branch->branch_prefix;

            // Start sequence from 1001 for each branch
            if (! isset($branchSequences[$prefix])) {
                $branchSequences[$prefix] = 1001;
            }

            $sequence = $branchSequences[$prefix];

            $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$sequence}";
            $monthYear     = now()->format('m_Y');

            PaymentInvoice::create([
                'student_id'     => $student->id,
                'total_amount'   => $payment->tuition_fee,
                'amount_due'     => $payment->tuition_fee,
                'invoice_number' => $invoiceNumber,
                'month_year'     => $monthYear,
            ]);

            $branchSequences[$prefix]++; // Increment for that branch
        }
    }
}
