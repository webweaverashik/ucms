<?php
namespace App\Console\Commands;

use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBirthdayWishSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send-birthday-wish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday wish SMS to students whose birthday is today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $students = Student::whereMonth('date_of_birth', $today->month)->whereDay('date_of_birth', $today->day)->with('mobileNumbers')->get();

        foreach ($students as $student) {
            $mobile = $student->mobileNumbers->where('number_type', 'sms')->first()?->mobile_number;

            if ($mobile) {
                try {
                    send_auto_sms('birthday_wish_message', $mobile, [
                        'student_name' => $student->name,
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Birthday SMS failed for {$student->name} ({$mobile}): " . $e->getMessage());
                }
            }
        }

        $this->info('Birthday wish SMS sent successfully.');
    }
}
