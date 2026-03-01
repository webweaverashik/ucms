<?php
namespace Database\Seeders;

use App\Models\ColumnSetting;
use Illuminate\Database\Seeder;

class ColumnSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default settings for students_index page
        $defaultStudentsSettings = [
            0  => true,  // counter (required)
            1  => true,  // student (required)
            2  => true,  // class
            3  => true,  // group
            4  => true,  // batch
            5  => true,  // institution
            6  => true,  // mobile_home
            7  => false, // mobile_sms
            8  => false, // mobile_whatsapp
            9  => false, // guardian_1
            10 => false, // guardian_2
            11 => false, // sibling_1
            12 => false, // sibling_2
            13 => true,  // tuition_fee
            14 => true,  // payment_type
            15 => false, // status
            16 => false, // admission_date
            17 => false, // admitted_by
            18 => true,  // actions (required)
        ];

        ColumnSetting::updateOrCreate(
            ['page' => 'students_index'],
            [
                'settings'   => $defaultStudentsSettings,
                'updated_by' => null,
            ]
        );

        $this->command->info('Column settings seeded successfully for students_index page.');
    }
}
