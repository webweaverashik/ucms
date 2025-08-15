<?php
namespace App\Models\SMS;

use App\Models\User;
use App\Models\Branch;
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
        'is_approved',
        'branch_id',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'recipients'  => 'string',
        'is_approved' => 'boolean',
    ];

    // Use your User model namespace if different
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
