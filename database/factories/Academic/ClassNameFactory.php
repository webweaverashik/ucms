<?php

namespace Database\Factories\Academic;

use App\Models\Academic\ClassName;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassNameFactory extends Factory
{

    // ------- This factory is not being used ---------

    protected $model = ClassName::class;

    public function definition(): array
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

        $name = $this->faker->randomElement(array_keys($classes));

        return [
            'name' => $name,
            'class_numeral' => $classes[$name],
        ];
    }
}
