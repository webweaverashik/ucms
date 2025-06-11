<?php
namespace Database\Factories\Sheet;

use App\Models\Academic\ClassName;
use App\Models\Sheet\Sheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class SheetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Sheet::class;

    public function definition()
    {
        return [
            'class_id' => ClassName::inRandomOrder()->value('id'),
            'price'    => 2000,
        ];
    }
}
