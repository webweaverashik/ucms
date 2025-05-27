<?php
namespace Database\Seeders\Payment;

use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = PaymentInvoice::where('status', '!=', 'paid')->get();

        foreach ($invoices as $invoice) {
            $totalAmount = $invoice->total_amount;
            $remaining   = $totalAmount;

            // Randomly decide whether to make full or partial payments (1 to 3 transactions)
            $transactionCount = rand(1, 3);

            for ($i = 1; $i <= $transactionCount && $remaining > 0; $i++) {
                $isLastTransaction = ($i === $transactionCount || $remaining < 100);

                if ($isLastTransaction) {
                    $amountPaid = $remaining;
                } else {
                    $amountPaid = round(rand(100, $remaining - 50), 2);
                }

                $paymentType = ($amountPaid == $remaining) ? 'full' : 'partial';

                PaymentTransaction::create([
                    'student_id'         => $invoice->student_id,
                    'payment_invoice_id' => $invoice->id,
                    'payment_type'       => $paymentType,
                    'amount_paid'        => $amountPaid,
                    'voucher_no'         => strtoupper(Str::random(10)),
                    'created_by'         => 2,
                ]);

                $remaining -= $amountPaid;
            }

            // Update invoice after transaction(s)
            $invoice->amount_due = max(0, $remaining);
            $invoice->status     = ($invoice->amount_due == 0) ? 'paid' : 'partially_paid';
            $invoice->save();
        }
    }
}
