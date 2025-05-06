<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use Illuminate\Database\Seeder;

class ClassNameSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            'Class IV'   => '04',
            'Class V'    => '05',
            'Class VI'   => '06',
            'Class VII'  => '07',
            'Class VIII' => '08',
            'Class IX'   => '09',
            'Class X'    => '10',
            'Class XI'   => '11',
            'Class XII'  => '12',
        ];

        foreach ($classes as $name => $numeral) {
            ClassName::firstOrCreate([
                'name'          => $name,
                'class_numeral' => $numeral,
            ]);
        }
    }
}
