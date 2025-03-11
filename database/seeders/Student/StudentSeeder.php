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
        // In your StudentSeeder, before creating students:
        $faker = \Faker\Factory::create();
        $faker->unique(true); // Reset the unique generator

        // Then, create your students using the factory
        Student::factory()->count(30)->create(); // Or whatever number
    }
}
