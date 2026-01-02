<?php
namespace App\Models;

use App\Models\Payment\PaymentTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWalletLog extends Model
{
    public $timestamps = false;

    public const TYPE_COLLECTION = 'collection';
    public const TYPE_SETTLEMENT = 'settlement';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'user_id',
        'type',
        'old_balance',
        'new_balance',
        'amount',
        'payment_transaction_id',
        'description',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'old_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'amount'      => 'decimal:2',
        'created_at'  => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });

        static::updating(function ($model) {
            throw new \RuntimeException('Wallet logs cannot be modified.');
        });

        static::deleting(function ($model) {
            throw new \RuntimeException('Wallet logs cannot be deleted.');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeCollections($query)
    {
        return $query->where('type', self::TYPE_COLLECTION);
    }

    public function scopeSettlements($query)
    {
        return $query->where('type', self::TYPE_SETTLEMENT);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCollection(): bool
    {
        return $this->type === self::TYPE_COLLECTION;
    }

    public function isSettlement(): bool
    {
        return $this->type === self::TYPE_SETTLEMENT;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_COLLECTION => 'Collection',
            self::TYPE_SETTLEMENT => 'Settlement',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            default               => 'Unknown',
        };
    }
}
