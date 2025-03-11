<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Shift;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Morning', 'Evening']),
            'branch_id' => Branch::inRandomOrder()->value('id') ?? Branch::factory()->create()->id,
            'deleted_by' => null,
        ];
    }
}
