<?php

namespace Database\Seeders\Academic;

use Illuminate\Database\Seeder;
use App\Models\Academic\SubjectTaken;

class SubjectTakenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubjectTaken::factory(100)->create(); // Generates 200 subject enrollments
    }
}
