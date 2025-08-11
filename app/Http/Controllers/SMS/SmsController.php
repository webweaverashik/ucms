<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Models\SMS\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // SMS Logs
    public function smsLog()
    {
        $smsLogs = SmsLog::with('createdBy')->orderBy('created_at', 'desc')->get();

        return view('sms.logs', compact('smsLogs'));
    }

    // Send single SMS
    public function sendSingle(Request $request)
    {
        $data = $request->validate([
            'mobile'       => 'required|string',
            'message_body' => 'required|string',
            'message_type' => 'required|in:TEXT,UNICODE',
        ]);

        $userId = Auth::id();

        $log = $this->smsService->sendSingleSms($data['mobile'], $data['message_body'], $data['message_type'], $userId);

        if ($log->status === 'SUCCESS') {
            return response()->json(['message' => 'SMS sent successfully.']);
        } else {
            return response()->json(['message' => 'SMS sending failed.', 'error' => $log->api_error], 500);
        }
    }

    // Send bulk SMS
    public function sendBulk(Request $request)
    {
        $data = $request->validate([
            'recipients'     => 'required|array|min:1',
            'message_body'   => 'required|string',
            'campaign_title' => 'required|string',
            'message_type'   => 'required|in:TEXT,UNICODE',
            'is_promotional' => 'sometimes|boolean',
        ]);

        $userId = Auth::id();

        $campaign = $this->smsService->sendBulkSms($data['recipients'], $data['message_body'], $data['campaign_title'], $data['is_promotional'] ?? false, $data['message_type'], $userId);

        if ($campaign->status === 'SENT') {
            return response()->json(['message' => 'Bulk SMS campaign sent successfully.']);
        } else {
            return response()->json(['message' => 'Bulk SMS campaign failed to send.'], 500);
        }
    }

    // Check SMS balance
    public function checkBalance()
    {
        $balance = $this->smsService->checkBalance();

        if (($balance['api_response_code'] ?? 0) === 200) {
            return response()->json(['balance' => $balance['balance']['sms']]);
        } else {
            return response()->json(['message' => 'Failed to fetch balance.'], 500);
        }
    }

    // Check SMS status by SMS UID
    public function checkSmsStatus(Request $request)
    {
        $request->validate([
            'sms_uid' => 'required|string',
        ]);

        $status = $this->smsService->checkSmsStatus($request->sms_uid);

        if (($status['api_response_code'] ?? 0) === 200) {
            return response()->json(['sms' => $status['sms']]);
        } else {
            return response()->json(['message' => 'Failed to fetch SMS status.'], 500);
        }
    }

}
