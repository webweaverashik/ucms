<?php
namespace App\Services;

use App\Models\SMS\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
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
    }

    /**
     * Check SMS balance
     */
    public function checkBalance(): array
    {
        $response = $this->makeApiRequest('/check-balance', [
            'api_key'    => $this->apiKey,
            'api_secret' => $this->apiSecret,
        ]);

        return $response->json();
    }

    /**
     * Check SMS status
     */
    public function checkSmsStatus(string $smsUid): array
    {
        $response = $this->makeApiRequest('/sms-status', [
            'api_key'    => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'sms_uid'    => $smsUid,
        ]);

        $data = $response->json();

        if ($smsLog = SmsLog::where('sms_uid', $smsUid)->first()) {
            $smsLog->update([
                'status'               => $data['sms']['sms_status'] ?? ($data['api_response_message'] ?? 'UNKNOWN'),
                'api_response_message' => $data['api_response_message'] ?? null,
                'api_error'            => $data['error'] ?? null,
            ]);
        }

        return $data;
    }

    /**
     * Helper: make HTTP POST request to SMS API
     */
    protected function makeApiRequest(string $endpoint, array $data)
    {
        $url = $this->baseUrl . $endpoint;

        $response = Http::acceptJson()
            ->timeout(10)
            ->post($url, $data);

        return $response;
    }
}
