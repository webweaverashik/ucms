<?php
namespace App\Services;

use App\Models\SMS\SmsLog;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    // Constants for message types
    const TEXT    = 'TEXT';
    const UNICODE = 'UNICODE';

    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey    = config('sms.api_key');
        $this->apiSecret = config('sms.api_secret');
        $this->baseUrl   = rtrim(config('sms.base_url'), '/');
    }

    /**
     * Send single SMS
     */
    public function sendSingleSms(string $mobile, string $message, string $messageType = self::TEXT, ?int $userId = null): SmsLog
    {
        try {
            $this->validateMobile($mobile);
            $this->validateMessage($message, $messageType);

            $response = $this->makeApiRequest('/send-sms', [
                'api_key'      => $this->apiKey,
                'api_secret'   => $this->apiSecret,
                'request_type' => 'SINGLE_SMS',
                'message_type' => $messageType,
                'mobile'       => $mobile,
                'message_body' => $message,
            ]);

            $data = $response->json();

            Log::channel('sms')->debug('Single SMS API response', [
                'mobile'   => $mobile,
                'response' => $data,
            ]);

            return SmsLog::create([
                'message_type'         => $messageType,
                'recipient'            => $mobile,
                'message_body'         => $message,
                'sms_uid'              => $data['sms_uid'] ?? null,
                'status'               => ($data['api_response_message'] ?? '') === 'SUCCESS' ? 'SUCCESS' : 'FAILED',
                'api_response_code'    => $data['api_response_code'] ?? null,
                'api_response_message' => $data['api_response_message'] ?? 'FAILED',
                'api_error'            => $data['error'] ?? null,
                'created_by'           => $userId,
            ]);
        } catch (Exception $e) {
            Log::channel('sms')->error('Single SMS failed', [
                'mobile' => $mobile,
                'error'  => $e->getMessage(),
            ]);

            return $this->createFailedLog($messageType, $mobile, $message, $userId, $e->getMessage());
        }
    }

    /**
     * Check SMS balance
     */
    public function checkBalance(): array
    {
        try {
            $response = $this->makeApiRequest('/check-balance', [
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ]);

            return $response->json();
        } catch (Exception $e) {
            Log::channel('sms')->error('Check balance failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'balance' => null,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Check SMS status
     */
    public function checkSmsStatus(string $smsUid): array
    {
        try {
            $response = $this->makeApiRequest('/sms-status', [
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'sms_uid'    => $smsUid,
            ]);

            $data = $response->json();

            // Update SMS log if exists
            if ($smsLog = SmsLog::where('sms_uid', $smsUid)->first()) {
                $smsLog->update([
                    'status'       => $data['sms']['sms_status'] ?? ($data['api_response_message'] ?? 'UNKNOWN'),
                    'api_response' => json_encode($data),
                ]);
            }

            return $data;
        } catch (Exception $e) {
            Log::channel('sms')->error('Check SMS status failed', [
                'sms_uid' => $smsUid,
                'error'   => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Common method for API requests
     */
    protected function makeApiRequest(string $endpoint, array $data)
    {
        $url = $this->baseUrl . $endpoint;

        $response = Http::acceptJson()->timeout(30)->retry(3, 100)->post($url, $data);

        if ($response->failed()) {
            throw new Exception("API request failed with status: {$response->status()}");
        }

        return $response;
    }

    /**
     * Create a failed SMS log entry
     */
    protected function createFailedLog(string $messageType, string $mobile, string $message, ?int $userId, string $error): SmsLog
    {
        return SmsLog::create([
            'message_type'         => $messageType,
            'recipient'            => $mobile,
            'message_body'         => $message,
            'status'               => 'FAILED',
            'api_response_message' => $error,
            'created_by'           => $userId,
        ]);
    }

    /**
     * Validate mobile number format
     */
    protected function validateMobile(string $mobile): void
    {
        if (! preg_match('/^[0-9]{11,15}$/', $mobile)) {
            throw new Exception("Invalid mobile number format: {$mobile}");
        }
    }

    /**
     * Validate message based on type
     */
    protected function validateMessage(string $message, string $messageType): void
    {
        $length = Str::length($message);

        if ($messageType === self::UNICODE && $length > 70) {
            throw new Exception('Unicode message too long (max 70 characters)');
        }

        if ($messageType === self::TEXT && $length > 160) {
            throw new Exception('Text message too long (max 160 characters)');
        }
    }
}
