<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        // List of predefined branch names
        $branch_names = ['Khilgaon', 'Goran'];

        return [
            'branch_name' => $this->faker->randomElement($branch_names), // Randomly pick between Khilgaon or Goran
            'address' => $this->faker->address, // Use a real BD address
            'phone_number' => '01' . $this->faker->numberBetween(100000000, 999999999), // Valid BD 11-digit phone number
        ];
    }
}
