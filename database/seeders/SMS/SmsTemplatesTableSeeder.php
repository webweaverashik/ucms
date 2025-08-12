<?php
namespace Database\Seeders\SMS;

use App\Models\SMS\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        SmsTemplate::insert([
            [
                'title'        => 'transaction_sms',
                'body'         => 'Dear {name}, your payment of {amount} has been successful. {voucher_no}',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'title'        => 'birthday_wish',
                'body'         => 'Happy Birthday, {name}! Have a wonderful day!',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
