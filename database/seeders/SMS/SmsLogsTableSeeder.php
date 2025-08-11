<?php
namespace Database\Seeders\SMS;

use App\Models\SMS\SmsLog;
use Illuminate\Database\Seeder;

class SmsLogsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        SmsLog::insert([
            [
                'message_type'         => 'TEXT',
                'recipient'            => '017XXXXXXXX',
                'message_body'         => 'Dear Ashik, your transaction of 1000 BDT has been successful.',
                'campaign_uid'         => null,
                'sms_uid'              => 'S20250810A1',
                'status'               => 'SUCCESS',
                'api_response_code'    => 200,
                'api_response_message' => 'SUCCESS',
                'api_error'            => null,
                'created_by'           => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'request_type'         => 'GENERAL_CAMPAIGN',
                'message_type'         => 'TEXT',
                'recipient'            => '018XXXXXXXX',
                'message_body'         => 'Welcome to our service! Thank you for joining us.',
                'campaign_uid'         => 'CAMP20250810A',
                'sms_uid'              => 'S20250810B2',
                'status'               => 'PENDING',
                'api_response_code'    => null,
                'api_response_message' => null,
                'api_error'            => null,
                'created_by'           => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ]);
    }
}
