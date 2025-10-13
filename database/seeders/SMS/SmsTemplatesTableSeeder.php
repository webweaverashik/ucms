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
                'name'       => 'student_registration_success',
                'body'       => 'প্রিয় {student_name}, {student_class_name} শ্রেণি এবং {student_batch_name} ব্যাচে এ আপনার ভর্তি সম্পন্ন হয়েছে। আপনার  শিক্ষার্থী আইডি: {student_unique_id} এবং মাসিক বেতন: {tuition_fee} টাকা যা প্রতি মাসের {due_date} তারিখের মধ্যে পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'academic',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'attendance_alert_to_guardian',
                'body'       => 'সম্মানীত অভিভাবক, আপনার সন্তান {student_name} আজকে ক্লাসে অনুপস্থিত ছিলো। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'academic',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Invoices type templates
            [
                'name'       => 'tuition_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার {month_year} মাসের টিউশন ফি {amount} টাকা বকেয়া আছে। অনুগ্রহ করে {due_date} তারিখের মধ্যে পরিশোধ করুন। ইনভয়েস নং {invoice_no}। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'model_test_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার মডেল টেস্ট ফি এর একটি ইনভয়েস তৈরি হয়েছে। ইনভয়েস নং: {invoice_no}, পরিমাণ: {amount} টাকা। বকেয়াটি পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'exam_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার পরীক্ষার ফি এর একটি ইনভয়েস তৈরি হয়েছে। ইনভয়েস নং: {invoice_no}, পরিমাণ: {amount} টাকা। বকেয়াটি পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'sheet_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার শিট ফি এর একটি ইনভয়েস তৈরি হয়েছে। ইনভয়েস নং: {invoice_no}, পরিমাণ: {amount} টাকা। বকেয়াটি পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'book_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার বইয়ের ফি এর একটি ইনভয়েস তৈরি হয়েছে। ইনভয়েস নং: {invoice_no}, পরিমাণ: {amount} টাকা। বকেয়াটি পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'diary_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার ডায়েরি ফি এর একটি ইনভয়েস তৈরি হয়েছে। ইনভয়েস নং: {invoice_no}, পরিমাণ: {amount} টাকা। বকেয়াটি পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'others_fee_invoice_created',
                'body'       => 'প্রিয় {student_name}, আপনার অন্যান্য ফি এর একটি ইনভয়েস তৈরি হয়েছে। ইনভয়েস নং: {invoice_no}, পরিমাণ: {amount} টাকা। বকেয়াটি পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'guardian_tuition_fee_invoice_created',
                'body'       => 'সম্মানীত অভিভাবক, আপনার সন্তান {student_name}-এর {month_year} মাসের টিউশন ফি {amount} টাকা বকেয়া রয়েছে। অনুগ্রহ করে {due_date} তারিখের মধ্যে পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'student_due_invoice_reminder',
                'body'       => 'প্রিয় {student_name}, আপনার {month_year} মাসের বকেয়া টিউশন ফি {due_amount} টাকা যা {due_date} তারিখে পরিশোধের শেষ দিন। অনুগ্রহ করে সময়মতো পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'student_overdue_invoice_reminder',
                'body'       => 'প্রিয় {student_name}, আপনার {month_year} মাসের বকেয়া টিউশন ফি {due_amount} টাকা পরিশোধের শেষ দিন {due_date} পার হয়ে গিয়েছে। অনুগ্রহ করে দ্রুত বকেয়া পরিশোধ করুন। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Transactions type templates
            [
                'name'       => 'student_payment_success',
                'body'       => 'প্রিয় {student_name}, আমরা আপনার {paid_amount} টাকা পেমেন্ট পেয়েছি। ভাউচার নং: {voucher_no}, ইনভয়েস নং: {invoice_no}, বাকি: {remaining_amount} টাকা। ধন্যবাদান্তে, ইউনিক কোচিং',
                'type'       => 'transactions',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Others type templates
            [
                'name'       => 'birthday_wish_message',
                'body'       => 'শুভ জন্মদিন {student_name}! নতুন বছরে অনেক আনন্দ, ভালোবাসা ও সাফল্য কামনা করি।',
                'type'       => 'others',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }
}
