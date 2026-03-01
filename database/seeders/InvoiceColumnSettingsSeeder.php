<?php

namespace Database\Seeders;

use App\Models\ColumnSetting;
use Illuminate\Database\Seeder;

class InvoiceColumnSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default settings for invoices_due page
        // Column order: 0=sl, 1=invoice_number, 2=student_name, 3=mobile, 4=guardian_1, 5=guardian_2,
        // 6=class_name, 7=institution, 8=tuition_fee, 9=activation_status, 10=invoice_type,
        // 11=billing_month, 12=total_amount, 13=amount_due, 14=due_date, 15=status,
        // 16=last_comment, 17=created_at, 18=actions
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
                'settings' => $defaultDueSettings,
                'updated_by' => null,
            ]
        );

        // Default settings for invoices_paid page
        // Column order: 0=sl, 1=invoice_number, 2=student_name, 3=mobile, 4=guardian_1, 5=guardian_2,
        // 6=class_name, 7=institution, 8=tuition_fee, 9=activation_status, 10=invoice_type,
        // 11=total_amount, 12=billing_month, 13=due_date, 14=status, 15=last_comment, 16=paid_at
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
                'settings' => $defaultPaidSettings,
                'updated_by' => null,
            ]
        );

        $this->command->info('Column settings seeded successfully for invoices_due and invoices_paid pages.');
    }
}
