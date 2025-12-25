<?php
namespace Database\Seeders\Payment;

use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use Carbon\Carbon;
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

            // 1–3 transactions per invoice
            $transactionCount = rand(1, 3);

            /**
             * Base date for this invoice
             * Random date within current month
             */
            $baseDate = Carbon::now()
                ->startOfMonth()
                ->addDays(rand(0, now()->daysInMonth - 1))
                ->addHours(rand(9, 18))
                ->addMinutes(rand(0, 59));

            for ($i = 1; $i <= $transactionCount && $remaining > 0; $i++) {
                $isLastTransaction = $i === $transactionCount || $remaining < 100;

                if ($isLastTransaction) {
                    $amountPaid = $remaining;
                } else {
                    $amountPaid = round(rand(100, $remaining - 50), 2);
                }

                $paymentType = $amountPaid == $remaining ? 'full' : 'partial';

                // 🔹 Spread transactions by 0–5 days
                $transactionDate = (clone $baseDate)->addDays(rand(0, 5));

                PaymentTransaction::create([
                    'student_id'         => $invoice->student_id,
                    'student_classname'  => $invoice->student->class->name,
                    'payment_invoice_id' => $invoice->id,
                    'payment_type'       => $paymentType,
                    'amount_paid'        => $amountPaid,
                    'remaining_amount'   => $remaining,
                    'voucher_no'         => strtoupper(Str::random(10)),
                    'created_by'         => 2,

                    // ✅ THIS IS THE KEY
                    'created_at'         => $transactionDate,
                    'updated_at'         => $transactionDate,
                ]);

                $remaining -= $amountPaid;
            }

            // Update invoice status
            $invoice->amount_due = max(0, $remaining);
            $invoice->status     = $invoice->amount_due == 0 ? 'paid' : 'partially_paid';

            $invoice->save();
        }
    }
}
