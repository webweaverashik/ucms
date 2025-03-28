<?php

namespace Database\Factories\Payment;

use App\Models\Payment\Payment;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        // Fetch an existing student ID from the database
        $studentId = Student::inRandomOrder()->first()->id;

        return [
            'student_id' => $studentId,
            'payment_style' => $this->faker->randomElement(['current', 'due']),
            'due_date' => $this->faker->randomElement([ '1/7', '1/10', '1/15', '1/30']),
            'tuition_fee' => $this->faker->randomFloat(2, 1000, 5000),
        ];
    }
}

