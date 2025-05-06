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
            'due_date' => $this->faker->randomElement([ 7, 10, 15, 30]),
            'tuition_fee' => $this->faker->randomFloat(2, 1500, 5000),
        ];
    }
}

