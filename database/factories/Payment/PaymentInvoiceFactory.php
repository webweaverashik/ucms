<?php
namespace Database\Factories\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentInvoiceFactory extends Factory
{
    protected $model = PaymentInvoice::class;

    public function definition()
    {
        // ---- Not using this factory ----
        
        $student = Student::inRandomOrder()->first();

        $tuitionFee = Payment::where('student_id', $student->id)->inRandomOrder()->value('tuition_fee') ?? 0;

        return [
            'invoice_number' => $this->faker->unique()->numberBetween(1000, 9999),
            'student_id'     => $student->id,
            'amount'         => $tuitionFee,
            'month_year'     => date('m_Y', strtotime($this->faker->date())),
        ];
    }
}
