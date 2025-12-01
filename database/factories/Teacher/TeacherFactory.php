<?php
namespace Database\Factories\Teacher;

use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->name,
            'phone'       => '01' . $this->faker->numberBetween(300000000, 999999999),
            'email'       => $this->faker->unique()->safeEmail,
            'password'    => Hash::make('password'),
            'base_salary' => $this->faker->numberBetween(500, 1000),
            'gender'      => $this->faker->randomElement(['male', 'female']), // <-- Added
        ];
    }
}
