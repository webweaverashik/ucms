<?php
namespace Database\Factories\Payment;

use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
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
            'student_id'     => $studentId,
            'amount'         => $this->faker->randomFloat(2, 1500, 5000),
            'month_year'     => $this->faker->monthName() . '-' . $this->faker->year(),
        ];
    }
}
