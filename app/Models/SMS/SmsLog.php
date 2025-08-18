<?php
namespace App\Models\SMS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'message_type',
        'recipient',
        'message_body',
        'sms_uid',
        'status',
        'api_response_code',
        'api_response_message',
        'api_error',
        'created_by',
    ];

    protected $casts = [
        'api_error'       => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
