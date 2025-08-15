<?php
namespace Database\Seeders\SMS;

use App\Models\SMS\SmsCampaign;
use Illuminate\Database\Seeder;

class SmsCampaignsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        SmsCampaign::insert([
            [
                'campaign_title' => 'Welcome Campaign',
                'message_type'   => 'TEXT',
                'message_body'   => 'Welcome to our service! Thank you for joining us.',
                'recipients'     => '01920869809,01521453429',
                'branch_id'      => 1,
                'created_by'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
