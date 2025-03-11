<?php

namespace Database\Seeders\Student;

use App\Models\Student\Reference;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Creates 10 random references
        Reference::factory()->count(20)->create();
    }
}
