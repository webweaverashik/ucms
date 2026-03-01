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
        /*
        |--------------------------------------------------------------------------
        | students_index Page Settings
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | invoices_due Page Settings
        |--------------------------------------------------------------------------
        */
        $defaultDueSettings = [
            0 => true,   // sl (required)
            1 => true,   // invoice_number (required)
            2 => true,   // student_name
            3 => true,   // mobile
            4 => false,  // guardian_1
            5 => false,  // guardian_2
            6 => false,  // class_name
            7 => false,  // institution
            8 => false,  // tuition_fee
            9 => false,  // activation_status
            10 => true,  // invoice_type
            11 => true,  // billing_month
            12 => true,  // total_amount
            13 => true,  // amount_due
            14 => true,  // due_date
            15 => true,  // status
            16 => true,  // last_comment
            17 => true,  // created_at
            18 => true,  // actions (required)
        ];

        ColumnSetting::updateOrCreate(
            ['page' => 'invoices_due'],
            [
                'settings'   => $defaultDueSettings,
                'updated_by' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | invoices_paid Page Settings
        |--------------------------------------------------------------------------
        */
        $defaultPaidSettings = [
            0 => true,   // sl (required)
            1 => true,   // invoice_number (required)
            2 => true,   // student_name
            3 => true,   // mobile
            4 => false,  // guardian_1
            5 => false,  // guardian_2
            6 => false,  // class_name
            7 => false,  // institution
            8 => false,  // tuition_fee
            9 => false,  // activation_status
            10 => true,  // invoice_type
            11 => true,  // total_amount
            12 => true,  // billing_month
            13 => true,  // due_date
            14 => true,  // status
            15 => true,  // last_comment
            16 => true,  // paid_at
        ];

        ColumnSetting::updateOrCreate(
            ['page' => 'invoices_paid'],
            [
                'settings'   => $defaultPaidSettings,
                'updated_by' => null,
            ]
        );

        $this->command->info('Column settings seeded successfully for students_index, invoices_due and invoices_paid pages.');
    }
}