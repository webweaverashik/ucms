<?php

namespace Database\Seeders\Student;

use App\Models\Student\Guardian;
use Illuminate\Database\Seeder;

class GuardianSeeder extends Seeder
{
    public function run()
    {
        Guardian::factory()->count(50)->create(); // ✅ Creates 50 random guardians
    }
}
