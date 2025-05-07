<?php
namespace Database\Seeders\Student;

use App\Models\Student\Student;
use Illuminate\Database\Seeder;
use App\Models\Student\MobileNumber;

class MobileNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();

        foreach ($students as $student) {
            // 1. Create home number
            MobileNumber::factory()->create([
                'student_id'  => $student->id,
                'number_type' => 'home',
            ]);

            // 2. Create sms number
            MobileNumber::factory()->create([
                'student_id'  => $student->id,
                'number_type' => 'sms',
            ]);

            // 3. Optionally create whatsapp number (50% chance)
            if (rand(0, 1)) {
                MobileNumber::factory()->create([
                    'student_id'  => $student->id,
                    'number_type' => 'whatsapp',
                ]);
            }
        }
    }
}
