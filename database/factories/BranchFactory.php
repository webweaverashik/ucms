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
        $branch_names = ['Goran', 'Khilgaon'];
        static $index = 0; // Use a static variable to track the index

        $branch_name = $branch_names[$index % count($branch_names)]; // Get branch name serially
        $branch_prefix = substr($branch_name, 0, 1);

        $index++; // Increment the index for the next call

        return [
            'branch_name' => $branch_name,
            'branch_prefix' => $branch_prefix,
            'address' => $this->faker->address,
            'phone_number' => '01' . $this->faker->numberBetween(100000000, 999999999),
        ];
    }
}
