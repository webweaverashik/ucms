<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\Academic\ShiftSeeder;
use Database\Seeders\Student\StudentSeeder;
use Database\Seeders\Teacher\TeacherSeeder;
use Database\Seeders\Student\ReferenceSeeder;
use Database\Seeders\Academic\ClassNameSeeder;
use Database\Seeders\Academic\InstitutionSeeder;
use Database\Seeders\Student\StudentActivationSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BranchSeeder::class,
            StudentSeeder::class,
            ClassNameSeeder::class,
            ShiftSeeder::class,
            InstitutionSeeder::class,
            TeacherSeeder::class,
            ReferenceSeeder::class,
            // StudentActivationSeeder::class, // will be called by StudentFactory
        ]);
    }
}
