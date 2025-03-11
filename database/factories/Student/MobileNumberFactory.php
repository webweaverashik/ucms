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
            'mobile_number' => '01' . $this->faker->numberBetween(100000000, 999999999), // Valid BD mobile number
            'number_type' => $this->faker->randomElement(['home', 'sms', 'whatsapp']),
            'student_id' => Student::inRandomOrder()->first()->id ?? Student::factory(),
            'deleted_by' => null, // Can be assigned later if needed
        ];
    }
}
