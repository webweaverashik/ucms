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
        // Fetch the template by title
        $template = SmsTemplate::where('name', $templateTitle)->first();

        if (! $template) {
            return ['error' => true, 'message' => "SMS template '{$templateTitle}' not found."];
        }

        // Replace placeholders in the template body with actual data
        $message = $template->body;

        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $message     = str_replace($placeholder, $value, $message);
        }

        // Send SMS using SmsService
        return $this->smsService->sendSingleSms($mobile, $message, $messageType, $userId);
    }
}
