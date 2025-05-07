<?php
namespace Database\Seeders\Payment;

use App\Models\Payment\Payment;
use App\Models\Student\Student;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        // Payment::factory()->count(50)->create();

        $students = Student::all();

        foreach ($students as $student) {
            // Skip if payment already exists (defensive check)
            if (! Payment::where('student_id', $student->id)->exists()) {
                Payment::factory()->create([
                    'student_id' => $student->id,
                ]);
            }
        }
    }
}
