<?php

namespace Database\Seeders\Student;

use Illuminate\Database\Seeder;
use App\Models\Student\StudentGuardian;
use App\Models\Student\Student;
use App\Models\Student\Guardian;

class StudentGuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $students = Student::all();

        foreach ($students as $student) {
            // Create 1 to 3 StudentGuardian records for each student.
            $guardianCount = rand(1, 3);

            // Create guardians
            $guardians = StudentGuardian::factory()
                ->count($guardianCount)
                ->create(['student_id' => $student->id]);

            // Set one guardian as primary
            $primaryGuardian = $guardians->random();
            $primaryGuardian->update(['is_primary' => true]);
        }
    }
}