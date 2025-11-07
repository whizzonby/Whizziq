<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalCheckIn extends Model
{
    protected $fillable = [
        'goal_id',
        'user_id',
        'notes',
        'progress_update',
        'sentiment',
        'key_result_updates',
        'blockers',
        'next_steps',
    ];

    protected $casts = [
        'key_result_updates' => 'array',
        'progress_update' => 'integer',
    ];

    /**
     * Get the goal that owns the check-in
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get the user that created the check-in
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get sentiment icon
     */
    public function getSentimentIconAttribute(): string
    {
        return match ($this->sentiment) {
            'positive' => 'heroicon-o-face-smile',
            'neutral' => 'heroicon-o-face-frown',
            'negative' => 'heroicon-o-face-sad',
            default => 'heroicon-o-minus',
        };
    }

    /**
     * Get sentiment color
     */
    public function getSentimentColorAttribute(): string
    {
        return match ($this->sentiment) {
            'positive' => 'success',
            'neutral' => 'warning',
            'negative' => 'danger',
            default => 'gray',
        };
    }
}
