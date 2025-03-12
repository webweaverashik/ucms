<?php

namespace Database\Factories\Academic;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Academic\SubjectTaken;
use App\Models\Academic\Subject;
use App\Models\Student\Student;

class SubjectTakenFactory extends Factory
{
    protected $model = SubjectTaken::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::inRandomOrder()->first()->id ?? Student::factory(),
            'subject_id' => Subject::inRandomOrder()->first()->id ?? Subject::factory(),
            'is_4th_subject' => $this->faker->boolean(20), // 20% chance of being a 4th subject
        ];
    }
}
