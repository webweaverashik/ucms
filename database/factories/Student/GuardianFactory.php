<?php

namespace Database\Factories\Student;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student\Guardian;
use App\Models\User;

class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'mobile_number' => '01' . $this->faker->numberBetween(100000000, 999999999), // Valid BD mobile number
            'gender' => $this->faker->randomElement(['male', 'female']),
            'address' => $this->faker->optional()->address,
            'deleted_by' => null, // Can be assigned later
        ];
    }
}
