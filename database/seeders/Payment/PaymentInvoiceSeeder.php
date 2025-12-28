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
        $tuitionInvoiceType = PaymentInvoiceType::where('type_name', 'Tuition Fee')->firstOrFail();

        $studentsWithCurrentPayment = Student::whereIn(
            'id',
            Payment::where('payment_style', 'current')->pluck('student_id')
        )->with('branch')->get();

        $yearSuffix = now()->format('y'); // e.g. 25
        $month      = now()->format('m'); // e.g. 05

        $branchSequences = [];

        foreach ($studentsWithCurrentPayment as $student) {

            $payment = Payment::where('student_id', $student->id)
                ->where('payment_style', 'current')
                ->first();

            if (! $payment || ! $student->branch) {
                continue;
            }

            $prefix = $student->branch->branch_prefix;

            // Start sequence from 1001 per branch
            if (! isset($branchSequences[$prefix])) {
                $branchSequences[$prefix] = 1001;
            }

            $invoiceNumber = sprintf(
                '%s%s%s_%d',
                $prefix,
                $yearSuffix,
                $month,
                $branchSequences[$prefix]
            );

            PaymentInvoice::create([
                'student_id'      => $student->id,
                'invoice_type_id' => $tuitionInvoiceType->id, // ✅ IMPORTANT
                'total_amount'    => $payment->tuition_fee,
                'amount_due'      => $payment->tuition_fee,
                'invoice_number'  => $invoiceNumber,
                'month_year'      => now()->format('m_Y'),
                'status'          => 'unpaid', // optional but recommended
            ]);

            $branchSequences[$prefix]++;
        }
    }
}
