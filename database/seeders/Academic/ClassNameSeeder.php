<?php

namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use Illuminate\Database\Seeder;

class ClassNameSeeder extends Seeder
{
    public function run(): void
    {
        ClassName::factory()->count(12)->create();
    }
}
