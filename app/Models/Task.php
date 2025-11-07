<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'priority',
        'status',
        'source',
        'due_date',
        'completed_at',
        'ai_priority_score',
        'ai_priority_reasoning',
        'linked_goal_id',
        'linked_document_id',
        'reminder_enabled',
        'reminder_date',
        'estimated_minutes',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'reminder_date' => 'datetime',
        'reminder_enabled' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linkedGoal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'linked_goal_id');
    }

    public function linkedDocument(): BelongsTo
    {
        return $this->belongsTo(DocumentVault::class, 'linked_document_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TaskTag::class, 'task_tag_pivot');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->whereDate('due_date', today());
    }

    public function scopeDueThisWeek(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->whereBetween('due_date', [now(), now()->addWeek()]);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent'])
            ->where('status', '!=', 'completed');
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    // Helper Methods
    public function isOverdue(): bool
    {
        if (!$this->due_date || $this->status === 'completed') {
            return false;
        }

        return $this->due_date->isPast();
    }

    public function isDueToday(): bool
    {
        if (!$this->due_date || $this->status === 'completed') {
            return false;
        }

        return $this->due_date->isToday();
    }

    public function isDueSoon(): bool
    {
        if (!$this->due_date || $this->status === 'completed') {
            return false;
        }

        return $this->due_date->isBetween(now(), now()->addDays(3));
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'primary',
            'low' => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityIconAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'heroicon-s-exclamation-triangle',
            'high' => 'heroicon-s-exclamation-circle',
            'medium' => 'heroicon-s-minus-circle',
            'low' => 'heroicon-s-chevron-down',
            default => 'heroicon-s-minus',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'in_progress' => 'primary',
            'pending' => 'gray',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'heroicon-s-check-circle',
            'in_progress' => 'heroicon-s-arrow-path',
            'pending' => 'heroicon-s-clock',
            'cancelled' => 'heroicon-s-x-circle',
            default => 'heroicon-s-minus-circle',
        };
    }

    public function getSourceIconAttribute(): string
    {
        return match ($this->source) {
            'manual' => 'heroicon-o-pencil',
            'document' => 'heroicon-o-document',
            'meeting' => 'heroicon-o-calendar',
            'voice' => 'heroicon-o-microphone',
            'ai_extracted' => 'heroicon-o-sparkles',
            default => 'heroicon-o-inbox',
        };
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function getEstimatedTimeHumanAttribute(): ?string
    {
        if (!$this->estimated_minutes) {
            return null;
        }

        if ($this->estimated_minutes < 60) {
            return $this->estimated_minutes . ' min';
        }

        $hours = floor($this->estimated_minutes / 60);
        $minutes = $this->estimated_minutes % 60;

        if ($minutes === 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $minutes . 'm';
    }

    public function hasAIPriority(): bool
    {
        return !empty($this->ai_priority_score);
    }

    public function getAIPriorityLevelAttribute(): ?string
    {
        if (!$this->ai_priority_score) {
            return null;
        }

        return match (true) {
            $this->ai_priority_score >= 80 => 'Critical',
            $this->ai_priority_score >= 60 => 'High',
            $this->ai_priority_score >= 40 => 'Medium',
            default => 'Low',
        };
    }
}
