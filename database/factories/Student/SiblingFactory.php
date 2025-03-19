<?php

namespace Database\Factories\Student;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use App\Models\Academic\Institution;

class SiblingFactory extends Factory
{
    protected $model = Sibling::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name,
            'age' => $this->faker->numberBetween(5, 14),
            'class' => 'Class ' . $this->faker->numberBetween(1, 12), // Random class name
            'institution_id' => Institution::inRandomOrder()->first()->id ?? Institution::factory(),
            'student_id' => Student::inRandomOrder()->first()->id ?? Student::factory(),
        ];
    }
}
