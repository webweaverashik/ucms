<?php

namespace Database\Seeders\Student;

use App\Models\Student\StudentActivation;
use Illuminate\Database\Seeder;

class StudentActivationSeeder extends Seeder
{
    public function run(): void
    {
        // Creates 10 random student activations
        StudentActivation::factory()->count(30)->create();
    }
}
