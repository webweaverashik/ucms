<?php

namespace Database\Factories\Student;

use App\Models\Student\Reference;
use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferenceFactory extends Factory
{
    protected $model = Reference::class;

    public function definition(): array
    {
        // Randomly assign a referer from either a student or teacher
        $referer = $this->faker->randomElement([Student::class, Teacher::class]);

        return [
            'referer_id' => $referer::factory(), // Create a new student or teacher via factory
            'referer_type' => class_basename($referer), // Assign the correct type (student or teacher)
        ];
    }
}
