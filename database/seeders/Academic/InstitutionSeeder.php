<?php

namespace Database\Seeders\Academic;

use App\Models\Academic\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        // Creates 10 institutions, with different types (school/college)
        Institution::factory()->count(10)->create();
    }
}
