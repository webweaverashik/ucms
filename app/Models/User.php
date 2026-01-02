<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'password',
        'branch_id',
        'is_active',
        'photo_url',
        'current_balance',
        'total_collected',
        'total_settled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'current_balance'   => 'decimal:2',
            'total_collected'   => 'decimal:2',
            'total_settled'     => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isAccountant(): bool
    {
        return $this->hasRole('accountant');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class, 'user_id')
            ->where('user_type', 'user');
    }

    public function latestLoginActivity()
    {
        return $this->hasOne(LoginActivity::class)
            ->where('user_type', 'user')
            ->latestOfMany()
            ->select('login_activities.*');
    }

    /**
     * Get wallet transaction logs for this user.
     */
    public function walletLogs()
    {
        return $this->hasMany(UserWalletLog::class)->latest('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Wallet Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get current wallet balance.
     */
    public function getWalletBalance(): float
    {
        return (float) ($this->current_balance ?? 0);
    }

    /**
     * Check if user has sufficient balance for settlement.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->getWalletBalance() >= $amount;
    }

    /**
     * Get today's collection total.
     */
    public function getTodayCollection(): float
    {
        return (float) $this->walletLogs()
            ->collections()
            ->today()
            ->sum('amount');
    }

    /**
     * Get today's settlement total.
     */
    public function getTodaySettlement(): float
    {
        return (float) abs($this->walletLogs()
                ->settlements()
                ->today()
                ->sum('amount'));
    }
}
