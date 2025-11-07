<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'category',
        'start_date',
        'target_date',
        'status',
        'progress_percentage',
        'ai_suggestions',
        'last_check_in_at',
        'check_in_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'last_check_in_at' => 'datetime',
        'progress_percentage' => 'integer',
        'check_in_count' => 'integer',
    ];

    /**
     * Get the user that owns the goal
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the key results for the goal
     */
    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class);
    }

    /**
     * Get the check-ins for the goal
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(GoalCheckIn::class);
    }

    /**
     * Calculate overall progress based on key results
     */
    public function calculateProgress(): void
    {
        $keyResults = $this->keyResults;

        if ($keyResults->isEmpty()) {
            return;
        }

        $totalProgress = $keyResults->sum('progress_percentage');
        $averageProgress = round($totalProgress / $keyResults->count());

        $this->update(['progress_percentage' => $averageProgress]);

        // Update status based on progress
        $this->updateStatus();
    }

    /**
     * Update status based on progress and timeline
     */
    public function updateStatus(): void
    {
        $daysRemaining = now()->diffInDays($this->target_date, false);
        $totalDays = $this->start_date->diffInDays($this->target_date);
        $expectedProgress = $totalDays > 0 ? ((($totalDays - $daysRemaining) / $totalDays) * 100) : 0;

        $progressDiff = $this->progress_percentage - $expectedProgress;

        // Determine status based on progress vs expected
        if ($this->progress_percentage >= 100) {
            $status = 'completed';
        } elseif ($progressDiff >= 10) {
            $status = 'on_track';
        } elseif ($progressDiff >= -10) {
            $status = 'in_progress';
        } elseif ($progressDiff >= -25) {
            $status = 'at_risk';
        } else {
            $status = 'off_track';
        }

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'on_track' => 'success',
            'in_progress' => 'primary',
            'at_risk' => 'warning',
            'off_track' => 'danger',
            'not_started' => 'gray',
            'abandoned' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'heroicon-o-check-circle',
            'on_track' => 'heroicon-o-arrow-trending-up',
            'in_progress' => 'heroicon-o-arrow-path',
            'at_risk' => 'heroicon-o-exclamation-triangle',
            'off_track' => 'heroicon-o-x-circle',
            'not_started' => 'heroicon-o-clock',
            'abandoned' => 'heroicon-o-archive-box',
            default => 'heroicon-o-flag',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'annual' => 'heroicon-o-calendar',
            'quarterly' => 'heroicon-o-calendar-days',
            'monthly' => 'heroicon-o-calendar',
            default => 'heroicon-o-flag',
        };
    }

    /**
     * Get category icon
     */
    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'revenue' => 'heroicon-o-currency-dollar',
            'customers' => 'heroicon-o-users',
            'product' => 'heroicon-o-cube',
            'team' => 'heroicon-o-user-group',
            'operational' => 'heroicon-o-cog',
            default => 'heroicon-o-flag',
        };
    }

    /**
     * Get category color
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'revenue' => 'success',
            'customers' => 'info',
            'product' => 'warning',
            'team' => 'primary',
            'operational' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->target_date, false);
    }

    /**
     * Check if goal is overdue
     */
    public function isOverdue(): bool
    {
        return $this->target_date->isPast() && $this->status !== 'completed';
    }

    /**
     * Check if needs check-in (weekly)
     */
    public function needsCheckIn(): bool
    {
        if (!$this->last_check_in_at) {
            return true;
        }

        return $this->last_check_in_at->diffInDays(now()) >= 7;
    }

    /**
     * Scope for active goals
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'abandoned']);
    }

    /**
     * Scope for off-track goals
     */
    public function scopeOffTrack($query)
    {
        return $query->whereIn('status', ['at_risk', 'off_track']);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
