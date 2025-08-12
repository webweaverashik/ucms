<?php
namespace App\Models\SMS;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
