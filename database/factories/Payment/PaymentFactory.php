<?php
namespace Database\Factories\Payment;

use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            // We'll assign `student_id` manually in the seeder
            'payment_style' => $this->faker->randomElement(['current', 'due']),
            'due_date'      => $this->faker->randomElement([7, 10, 15, 30]),
            'tuition_fee'   => $this->faker->randomFloat(2, 1500, 5000),
        ];
    }
}
