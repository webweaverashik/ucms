<?php
namespace App\Models\Cost;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cost extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'cost_date', 'created_by'];

    protected $casts = [
        'cost_date' => 'date',
    ];

    /**
     * Get the cost entries for this cost.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    /**
     * Calculate the total amount from all entries.
     */
    public function totalAmount(): int
    {
        return (int) $this->entries()->sum('amount');
    }

    /**
     * Get the total amount attribute.
     */
    public function getTotalAmountAttribute(): int
    {
        return $this->totalAmount();
    }

    /**
     * Get the branch that owns the cost.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the cost.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('cost_date', [$startDate, $endDate]);
    }
}
