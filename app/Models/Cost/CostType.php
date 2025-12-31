<?php
namespace App\Models\Cost;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostType extends Model
{
    protected $table = 'cost_types';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the cost entries for this cost type.
     */
    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    /**
     * Scope to get only active cost types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
