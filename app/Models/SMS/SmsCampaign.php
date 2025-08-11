<?php
namespace App\Models\SMS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campaign_title',
        'message_type',
        'message_body',
        'recipients',
        'exclude_inactive',
        'scheduled_at',
        'status',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'recipients'       => 'string',
        'exclude_inactive' => 'boolean',
        'scheduled_at'     => 'datetime',
        'is_approved'      => 'boolean',
    ];

    // Use your User model namespace if different
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
