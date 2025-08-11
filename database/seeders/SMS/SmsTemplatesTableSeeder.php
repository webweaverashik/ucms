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
                'title'        => 'Transaction SMS',
                'message_type' => 'TEXT',
                'body'         => 'Dear {name}, your transaction of {amount} has been successful.',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'title'        => 'Greetings SMS',
                'message_type' => 'UNICODE',
                'body'         => 'শুভেচ্ছা {name}, আপনার দিনটি আনন্দময় হোক।',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'title'        => 'Birthday Wish',
                'message_type' => 'TEXT',
                'body'         => 'Happy Birthday, {name}! Have a wonderful day!',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
