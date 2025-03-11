<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\Academic\ShiftSeeder;
use Database\Seeders\Student\StudentSeeder;
use Database\Seeders\Academic\ClassNameSeeder;

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
            // StudentSeeder::class,
            ClassNameSeeder::class,
            ShiftSeeder::class,
        ]);
    }
}
