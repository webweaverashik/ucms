<?php

namespace Database\Seeders\Academic;

use App\Models\Academic\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        // Shift::factory()->count(2)->create(); // Creates 2 shifts: Morning and Evening

        Shift::create([
            'name' => 'Morning',
            'branch_id' => 1,
        ]);
        
        Shift::create([
            'name' => 'Evening',
            'branch_id' => 1,
        ]);
        
        Shift::create([
            'name' => 'Morning',
            'branch_id' => 2,
        ]);

        Shift::create([
            'name' => 'Evening',
            'branch_id' => 2,
        ]);
    }
}
