<?php

namespace Database\Seeders\Academic;

use App\Models\Academic\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::factory()->count(3)->create(); // Creates 2 shifts: Morning and Evening
    }
}
