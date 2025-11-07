<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeyResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'metric_type',
        'start_value',
        'current_value',
        'target_value',
        'unit',
        'status',
        'progress_percentage',
    ];

    protected $casts = [
        'start_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'progress_percentage' => 'integer',
    ];

    /**
     * Get the goal that owns the key result
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Calculate progress percentage
     */
    public function calculateProgress(): void
    {
        $range = $this->target_value - $this->start_value;

        if ($range == 0) {
            $progress = 100;
        } else {
            $currentProgress = $this->current_value - $this->start_value;
            $progress = round(($currentProgress / $range) * 100);
            $progress = max(0, min(100, $progress)); // Clamp between 0-100
        }

        $this->update(['progress_percentage' => $progress]);

        // Update status based on progress
        $this->updateStatus();

        // Trigger parent goal progress calculation
        $this->goal->calculateProgress();
    }

    /**
     * Update status based on progress
     */
    public function updateStatus(): void
    {
        $status = match (true) {
            $this->progress_percentage >= 100 => 'completed',
            $this->progress_percentage >= 75 => 'on_track',
            $this->progress_percentage >= 50 => 'on_track',
            $this->progress_percentage >= 25 => 'at_risk',
            default => 'off_track',
        };

        if ($this->progress_percentage === 0 && $this->current_value === $this->start_value) {
            $status = 'not_started';
        }

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }

    /**
     * Get formatted current value with unit
     */
    public function getFormattedCurrentValueAttribute(): string
    {
        return $this->formatValue($this->current_value);
    }

    /**
     * Get formatted target value with unit
     */
    public function getFormattedTargetValueAttribute(): string
    {
        return $this->formatValue($this->target_value);
    }

    /**
     * Format value based on metric type
     */
    protected function formatValue($value): string
    {
        return match ($this->metric_type) {
            'currency' => '$' . number_format($value, 0),
            'percentage' => number_format($value, 1) . '%',
            'number' => number_format($value, 0) . ($this->unit ? ' ' . $this->unit : ''),
            'boolean' => $value ? 'Yes' : 'No',
            default => (string) $value . ($this->unit ? ' ' . $this->unit : ''),
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'on_track' => 'success',
            'at_risk' => 'warning',
            'off_track' => 'danger',
            'not_started' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get progress bar color
     */
    public function getProgressColorAttribute(): string
    {
        return match (true) {
            $this->progress_percentage >= 75 => 'success',
            $this->progress_percentage >= 50 => 'primary',
            $this->progress_percentage >= 25 => 'warning',
            default => 'danger',
        };
    }
}
