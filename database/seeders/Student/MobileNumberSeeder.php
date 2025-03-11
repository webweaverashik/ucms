<?php

namespace Database\Seeders\Student;

use Illuminate\Database\Seeder;
use App\Models\Student\MobileNumber;

class MobileNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MobileNumber::factory(100)->create(); // Generates 100 mobile numbers
    }
}
