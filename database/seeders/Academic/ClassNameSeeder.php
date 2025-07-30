<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use Illuminate\Database\Seeder;

class ClassNameSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            'Class IV'        => '04',
            'Class V'         => '05',
            'Class VI'        => '06',
            'Class VII'       => '07',
            'Class VIII'      => '08',
            'Class IX'        => '09',
            'SSC (25-26)'     => '10',
            'SSC (24-25)'     => '10',
            'HSC (25-26) Sci' => '11',
            'HSC (25-26) Com' => '11',
            'HSC (24-25) Sci' => '12',
            'HSC (24-25) Com' => '12',
        ];

        foreach ($classes as $name => $numeral) {
            ClassName::firstOrCreate([
                'name'          => $name,
                'class_numeral' => $numeral,
            ]);
        }
    }
}
