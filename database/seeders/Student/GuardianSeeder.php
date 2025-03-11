<?php

namespace Database\Seeders\Student;

use Illuminate\Database\Seeder;
use App\Models\Student\Guardian;

class GuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Guardian::factory(50)->create(); // Generates 50 guardians
    }
}
