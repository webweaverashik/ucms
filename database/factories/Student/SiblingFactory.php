<?php
namespace Database\Factories\Student;

use App\Models\Student\Sibling;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiblingFactory extends Factory
{
    protected $model = Sibling::class;

    public function definition(): array
    {
        return [
            'name'             => $this->faker->name,
            'year'             => $this->faker->numberBetween(2020, 2025),
            'class'            => 'Class ' . $this->faker->numberBetween(1, 5), // Random class name
            'institution_name' => $this->faker->name,
            'student_id'       => Student::inRandomOrder()->first()->id ?? Student::factory(),
        ];
    }
}
