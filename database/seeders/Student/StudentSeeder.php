<?php

namespace Database\Seeders\Student;

use App\Models\Student\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Student::factory()->count(50)->create(); // Create 50 students
    }
}