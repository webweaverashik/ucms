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
        $studentsWithCurrentPayment = Student::whereIn('id', Payment::where('payment_style', 'current')->pluck('student_id'))->get();

        $yearSuffix = now()->format('y'); // '25'
        $month      = now()->format('m'); // '05'
        $sequence   = 1001;

        foreach ($studentsWithCurrentPayment as $student) {
            $payment = Payment::where('student_id', $student->id)->where('payment_style', 'current')->inRandomOrder()->first();

            if (! $payment || ! $student->branch) {
                continue;
            }

            $prefix = $student->branch->branch_prefix;

            $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$sequence}";
            $monthYear = now()->format('m_Y');

            // Create the PaymentInvoice directly without using the factory
            PaymentInvoice::create([
                'student_id'     => $student->id,
                'amount'         => $payment->tuition_fee,
                'invoice_number' => $invoiceNumber,
                'month_year'     => $monthYear,
            ]);

            $sequence++;
        }
    }
}
