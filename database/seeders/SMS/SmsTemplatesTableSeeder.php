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
            // Academic type templates
            [
                'name'      => 'student_registration_success',
                'body'       => 'Dear {student_name}, your registration for {student_class_name} is successful. Student ID: {student_unique_id}',
                'type'       => 'academic',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Invoices type templates
            [
                'name'      => 'tuition_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice fee for tuition fee has been generated. Billing Month: {month_year}, Amount: {amount} tk. Please pay before {due_date}.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'model_test_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice for model test fee has been generated. Invoice No.: {invoice_no}, Amount: {amount} tk.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'exam_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice for exam test fee has been generated. Invoice No.: {invoice_no}, Amount: {amount} tk.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'sheet_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice for sheet fee has been generated. Invoice No.: {invoice_no}, Amount: {amount} tk.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'book_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice for book fee has been generated. Invoice No.: {invoice_no}, Amount: {amount} tk.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'diary_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice for diary fee has been generated. Invoice No.: {invoice_no}, Amount: {amount} tk.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'others_fee_invoice_created',
                'body'       => 'Dear {student_name}, an invoice for others fee has been generated. Invoice No.: {invoice_no}, Amount: {amount} tk.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'guardian_tuition_fee_invoice_created',
                'body'       => 'Dear Sir/Mam, tuition fee for {month_year} of {student_name} is due: {amount} BDT. Please pay by {due_date}. Thank you.',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'student_due_invoice_reminder',
                'body'       => 'Dear {student_name}, your invoice {invoice_no} for {invoice_type} with amount {due_amount} is due on {due_date}. Please make the payment on time to avoid any inconvenience. Thank you!',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'      => 'student_overdue_invoice_reminder',
                'body'       => 'Dear {student_name}, your invoice {invoice_no} for {invoice_type} with amount {due_amount} is overdue. The last date was {due_date}. Thank you!',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Transactions type templates
            [
                'name'      => 'student_payment_success',
                'body'       => 'Dear {student_name}, we have received your payment of {paid_amount} tk. Voucher No: {voucher_no}, Invoice No: {invoice_no}, Due: {remaining_amount} tk. Thanks for the payment.',
                'type'       => 'transactions',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Others type templates
            [
                'name'      => 'birthday_wish_message',
                'body'       => 'Happy Birthday, {student_name}! Wishing you a year full of success, happiness, and good health. Enjoy your special day!',
                'type'       => 'others',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
