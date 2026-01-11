<?php
namespace Database\Seeders\Payment;

use App\Models\Payment\PaymentInvoiceType;
use Illuminate\Database\Seeder;

class PaymentInvoiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Tuition Fee',
            'Admission Fee',
            'Sheet Fee',
            'Book Fee',
            'Diary Fee',
            'Exam Fee',
            'Model Test Fee',
            'Special Class Fee',
        ];

        foreach ($types as $type) {
            PaymentInvoiceType::firstOrCreate([
                'type_name' => $type,
            ]);
        }
    }
}
