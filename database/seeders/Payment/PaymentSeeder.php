<?php

namespace Database\Seeders\Payment;

use App\Models\Payment\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        Payment::factory()->count(40)->create();
    }
}
