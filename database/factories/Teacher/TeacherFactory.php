<?php

namespace Database\Factories\Teacher;

use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name, // Random name for the teacher
            'phone' => '01' . $this->faker->numberBetween(100000000, 999999999), // Valid BD phone number (11 digits)
            'email' => $this->faker->unique()->safeEmail, // Unique email
            'password' => bcrypt('password'), // Default password for the teacher
            'photo_url' => $this->faker->imageUrl(640, 480, 'people'), // Random teacher photo URL
            'base_salary' => $this->faker->numberBetween(1000, 2000), // Random salary between 1,000 and 2,000
            'deleted_by' => null, // Assuming null for now, can be set as per requirements
        ];
    }
}
