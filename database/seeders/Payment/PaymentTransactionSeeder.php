<?php

namespace Database\Seeders\Payment;

use App\Models\Payment\PaymentTransaction;
use Illuminate\Database\Seeder;

class PaymentTransactionSeeder extends Seeder
{
    public function run()
    {
        PaymentTransaction::factory()->count(10)->create();
    }
}

