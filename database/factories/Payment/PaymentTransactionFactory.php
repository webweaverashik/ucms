<?php

namespace Database\Factories\Payment;

use App\Models\Student\Student;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition()
    {
        // Fetch an existing student ID from the database
        $studentId = Student::inRandomOrder()->first()->id;

        // Fetch an existing payment invoice ID from the database
        $paymentInvoiceId = PaymentInvoice::inRandomOrder()->first()->id;

        return [
            'student_id' => $studentId,
            'payment_invoice_id' => $paymentInvoiceId,
            'payment_type' => $this->faker->randomElement(['partial', 'full']),
            'amount_paid' => $this->faker->randomFloat(2, 500, 2000),
            'voucher_no' => $this->faker->unique()->word(),
        ];
    }
}
