<?php

namespace Database\Seeders\Student;

use Illuminate\Database\Seeder;
use App\Models\Student\Sibling;

class SiblingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sibling::factory(20)->create(); // Generates 50 sibling records
    }
}
