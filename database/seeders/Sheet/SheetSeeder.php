<?php
namespace Database\Seeders\Sheet;

use App\Models\Sheet\Sheet;
use Illuminate\Database\Seeder;
use App\Models\Academic\ClassName;

class SheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClassName::all()->each(function ($class) {
            Sheet::factory()->create([
                'class_id' => $class->id,
            ]);
        });
    }
}
