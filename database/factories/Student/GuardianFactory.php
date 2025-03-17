<?php

namespace Database\Factories\Student;

use App\Models\Student\Student;
use App\Models\Student\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'mobile_number' => '01' . $this->faker->numberBetween(100000000, 999999999), // Valid 11-digit BD number
            'gender' => $this->faker->randomElement(['male', 'female']),
            'address' => $this->faker->address,
            'relationship' => $this->faker->randomElement(['father', 'mother', 'brother', 'sister', 'uncle']),
            'student_id' => Student::inRandomOrder()->first()->id ?? Student::factory(), // Ensure a student exists
            'deleted_by' => null,
        ];
    }
}
