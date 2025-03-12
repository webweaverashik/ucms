<?php

namespace Database\Factories\Academic;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Academic\Subject;
use App\Models\Academic\ClassName;
use App\Models\User;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'subject_name' => $this->faker->randomElement([
                'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Accounting', 'Finance',
                'Business Studies', 'Economics', 'History', 'Geography', 'English', 'Bangla'
            ]),
            'academic_group' => $this->faker->randomElement(['General', 'Science', 'Commerce', 'Arts']),
            'is_mandatory' => $this->faker->boolean(70), // 70% chance of being mandatory
            'class_id' => ClassName::inRandomOrder()->first()->id ?? ClassName::factory(),
            'deleted_by' => null, // Can be assigned later
        ];
    }
}
