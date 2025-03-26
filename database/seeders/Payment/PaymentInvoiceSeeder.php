<?php

namespace Database\Seeders\Payment;

use App\Models\Payment\PaymentInvoice;
use Illuminate\Database\Seeder;

class PaymentInvoiceSeeder extends Seeder
{
    public function run()
    {
        PaymentInvoice::factory()->count(10)->create();
    }
}

