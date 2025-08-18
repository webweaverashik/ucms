<?php
namespace App\Services;

use App\Models\SMS\SmsTemplate;

class AutoSmsService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send auto SMS using a template title and data replacements.
     *
     * @param string $templateTitle Template title like 'student_registration_success'
     * @param string $mobile Recipient mobile number
     * @param array $data Associative array of placeholders and their replacements
     * @param string $messageType TEXT or UNICODE
     * @param int|null $userId ID of user sending the SMS
     *
     * @return \App\Models\SMS\SmsLog|array SmsLog model or error array
     */
    public function sendAutoSms(string $templateTitle, string $mobile, array $data = [], string $messageType = 'TEXT', ?int $userId = null)
    {
        // Fetch active template by title
        $template = SmsTemplate::where('name', $templateTitle)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return ['error' => true, 'message' => "Active SMS template '{$templateTitle}' not found or inactive."];
        }

        // Replace placeholders in the template body
        $message = $template->body;
        foreach ($data as $key => $value) {
            $message = str_replace("{" . $key . "}", $value, $message);
        }

        // Send SMS
        return $this->smsService->sendSingleSms($mobile, $message, $messageType, $userId);
    }
}
