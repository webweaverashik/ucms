<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company, // Fake institution name
            'eiin_number' => $this->faker->unique()->numerify('######'), // Unique 6-digit EIIN number
            'type' => $this->faker->randomElement(['school', 'college']), // Randomize between 'school' and 'college'
            'deleted_by' => null, // Assuming null for now, can be set as per requirements
        ];
    }
}
