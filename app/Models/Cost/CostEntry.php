<?php

namespace App\Models\Cost;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostEntry extends Model
{
    protected $table = 'cost_entries';

    protected $fillable = ['cost_id', 'cost_type_id', 'amount'];

    protected $casts = [
        'amount' => 'integer',
    ];

    /**
     * Get the cost that owns the entry.
     */
    public function cost(): BelongsTo
    {
        return $this->belongsTo(Cost::class);
    }

    /**
     * Get the cost type for this entry.
     */
    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }
}