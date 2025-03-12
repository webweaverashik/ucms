<?php

namespace Database\Seeders\Academic;

use Illuminate\Database\Seeder;
use App\Models\Academic\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subject::factory(50)->create(); // Generates 50 subject records
    }
}
