<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use Illuminate\Database\Seeder;

class ClassNameSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['name' => 'Class IV',        'class_numeral' => '04', 'is_active' => 1],
            ['name' => 'Class V',         'class_numeral' => '05', 'is_active' => 1],
            ['name' => 'Class VI',        'class_numeral' => '06', 'is_active' => 1],
            ['name' => 'Class VII',       'class_numeral' => '07', 'is_active' => 1],
            ['name' => 'Class VIII',      'class_numeral' => '08', 'is_active' => 1],
            ['name' => 'Class IX',        'class_numeral' => '09', 'is_active' => 1],
            ['name' => 'SSC (25-26)',     'class_numeral' => '10', 'is_active' => 1],
            ['name' => 'SSC (24-25)',     'class_numeral' => '10', 'is_active' => 0],
            ['name' => 'HSC (26-27) Sci', 'class_numeral' => '11', 'is_active' => 1],
            ['name' => 'HSC (26-27) Com', 'class_numeral' => '11', 'is_active' => 1],
            ['name' => 'HSC (25-26) Sci', 'class_numeral' => '12', 'is_active' => 1],
            ['name' => 'HSC (25-26) Com', 'class_numeral' => '12', 'is_active' => 1],
            ['name' => 'HSC (24-25) Sci', 'class_numeral' => '12', 'is_active' => 0],
            ['name' => 'HSC (24-25) Com', 'class_numeral' => '12', 'is_active' => 0],
        ];

        foreach ($classes as $class) {
            ClassName::updateOrCreate(
                [
                    'name'          => $class['name'],
                    'class_numeral' => $class['class_numeral'],
                ],
                [
                    'is_active'     => $class['is_active'],
                ]
            );
        }
    }
}

