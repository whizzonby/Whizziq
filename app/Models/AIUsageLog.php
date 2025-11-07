<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'action',
        'tokens_used',
        'cost_cents',
        'prompt_summary',
        'metadata',
        'requested_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'tokens_used' => 'integer',
        'cost_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get cost in dollars
     */
    public function getCostDollarsAttribute(): float
    {
        return $this->cost_cents / 100;
    }
}
