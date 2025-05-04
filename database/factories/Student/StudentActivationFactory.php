<?php

namespace Database\Factories\Student;

use App\Models\Student\Student;
use App\Models\User;
use App\Models\Student\StudentActivation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentActivationFactory extends Factory
{
    protected $model = StudentActivation::class;

    public function definition(): array
    {
        return [
            'student_id' => null, // ✅ Will be set from StudentFactory
            'active_status' => $this->faker->randomElement(['active', 'inactive']),
            'reason' => $this->faker->sentence(),
            'updated_by' => User::inRandomOrder()->first()->id,
        ];
    }
}
