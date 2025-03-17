<?php

namespace Database\Seeders\Academic;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use App\Models\Academic\ClassName;

class ClassNameSeeder extends Seeder
{
    // public function run(): void
    // {
    //     ClassName::factory()->count(12)->create(); // it may create duplicate classes
    // }

    public function run(): void
    {
        $classes = [
            'Class I' => '01',
            'Class II' => '02',
            'Class III' => '03',
            'Class IV' => '04',
            'Class V' => '05',
            'Class VI' => '06',
            'Class VII' => '07',
            'Class VIII' => '08',
            'Class IX' => '09',
            'Class X' => '10',
            'Class XI' => '11',
            'Class XII' => '12',
        ];

        foreach ($classes as $name => $numeral) {
            ClassName::firstOrCreate([
                'name' => $name,
                'class_numeral' => $numeral,
                'branch_id' => Branch::inRandomOrder()->first()->id ?? Branch::factory(), // Ensure BranchFactory exists or modify as needed
            ]);
        }
    }
}
