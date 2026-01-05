<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use Illuminate\Database\Seeder;

class ClassNameSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['name' => 'Class 04', 'class_numeral' => '04'],
            ['name' => 'Class 05', 'class_numeral' => '05'],
            ['name' => 'Class 06', 'class_numeral' => '06'],
            ['name' => 'Class 07', 'class_numeral' => '07'],
            ['name' => 'Class 08', 'class_numeral' => '08'],
            ['name' => 'Class 09', 'class_numeral' => '09'],
            ['name' => 'SSC (26-27)', 'class_numeral' => '10'],
            ['name' => 'SSC (25-26)', 'class_numeral' => '10'],
            ['name' => 'HSC (26-27)', 'class_numeral' => '11'],
            ['name' => 'HSC (25-26)', 'class_numeral' => '12'],
        ];

        foreach ($classes as $class) {
            ClassName::updateOrCreate(
                [
                    'name'          => $class['name'],
                    'class_numeral' => $class['class_numeral'],
                ]
            );
        }
    }
}
