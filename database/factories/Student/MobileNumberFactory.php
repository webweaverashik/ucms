<?php

namespace Database\Factories\Student;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student\MobileNumber;
use App\Models\Student\Student;

class MobileNumberFactory extends Factory
{
    protected $model = MobileNumber::class;

    public function definition(): array
    {
        return [
            'mobile_number' => '01' . $this->faker->numberBetween(300000000, 999999999), // Valid BD mobile number
            'number_type'   => 'home', // default type, will override in seeder
        ];
    }
}
