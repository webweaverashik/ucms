<?php
namespace Database\Seeders\Payment;

use Illuminate\Database\Seeder;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;

class PaymentTransactionSeeder extends Seeder
{
    public function run()
    {
        $invoices = PaymentInvoice::with('student')->get();

        foreach ($invoices as $invoice) {
            // Decide number of transactions per invoice (1 or 2)
            $transactionCount = rand(1, 2);
            $remainingAmount  = $invoice->amount;

            for ($i = 1; $i <= $transactionCount; $i++) {
                // Generate a random amount for partial payments or full for the last
                if ($i < $transactionCount) {
                    $amountPaid  = round($remainingAmount * 0.5, 2);
                    $paymentType = 'partial';
                } else {
                    $amountPaid  = $remainingAmount;
                    $paymentType = $i === 1 ? 'full' : 'partial'; // If only 1 txn, it's full
                }

                $voucherNo = 'TXN_' . $invoice->invoice_number . '_' . str_pad($i, 2, '0', STR_PAD_LEFT);

                PaymentTransaction::create([
                    'student_id'         => $invoice->student_id,
                    'payment_invoice_id' => $invoice->id,
                    'payment_type'       => $paymentType,
                    'amount_paid'        => $amountPaid,
                    'voucher_no'         => $voucherNo,
                ]);

                $remainingAmount -= $amountPaid;
            }
        }
    }
}
