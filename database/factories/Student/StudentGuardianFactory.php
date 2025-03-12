<?php

namespace Database\Factories\Student;

use App\Models\Student\StudentGuardian;
use App\Models\Student\Student;
use App\Models\Student\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentGuardianFactory extends Factory
{
    protected $model = StudentGuardian::class;

    public function definition(): array
    {
        // Get random Student and Guardian IDs or create new ones
        $studentId = Student::inRandomOrder()->first()->id ?? Student::factory()->create()->id;
        $guardianId = Guardian::inRandomOrder()->first()->id ?? Guardian::factory()->create()->id;

        return [
            'student_id' => $studentId,
            'guardian_id' => $guardianId,
            'relationship' => $this->faker->randomElement(['Father', 'Mother', 'Uncle', 'Aunt', 'Brother', 'Sister']),
            'is_primary' => false, // Initially, all are non-primary
        ];
    }

    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => true,
            ];
        });
    }
}