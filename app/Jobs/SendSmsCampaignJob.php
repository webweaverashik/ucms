<?php
namespace App\Jobs;

use App\Models\SMS\SmsCampaign;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SmsCampaign $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(SmsCampaign $campaign)
    {
        // Keep only ID, avoid serializing large recipients array
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        $campaign = SmsCampaign::find($this->campaign->id);

        if (! $campaign || ! $campaign->is_approved) {
            return;
        }

        $recipients = json_decode($campaign->recipients, true) ?? [];

        foreach ($recipients as $mobile) {
            try {
                $smsLog = $smsService->sendSingleSms($mobile, $campaign->message_body, $campaign->message_type, $campaign->created_by);

                Log::channel('sms')->info('Campaign SMS sent', [
                    'campaign_id' => $campaign->id,
                    'recipient'   => $mobile,
                    'status'      => $smsLog->status,
                ]);

                usleep(200000); // 0.2 sec // Optional: small delay to avoid API rate-limit
            } catch (\Throwable $e) {
                Log::channel('sms')->error('Failed to send campaign SMS', [
                    'campaign_id' => $campaign->id,
                    'recipient'   => $mobile,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }
}
