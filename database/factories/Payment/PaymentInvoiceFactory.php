<?php

namespace Database\Factories\Payment;

use App\Models\Student\Student;
use App\Models\Payment\PaymentInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentInvoiceFactory extends Factory
{
    protected $model = PaymentInvoice::class;

    public function definition()
    {
        // Fetch an existing student ID from the database
        $studentId = Student::inRandomOrder()->first()->id;

        return [
            'invoice_number' => $this->faker->unique()->numberBetween(1000, 9999),
            'student_id' => $studentId,
            'month_year' => $this->faker->monthName() . '-' . $this->faker->year(),
        ];
    }
}

