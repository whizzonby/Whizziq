<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactInteraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'contact_id',
        'deal_id',
        'type',
        'subject',
        'description',
        'interaction_date',
        'duration_minutes',
        'outcome',
        'attachments',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
        'duration_minutes' => 'integer',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('interaction_date', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('interaction_date', today());
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'call' => 'Call',
            'email' => 'Email',
            'meeting' => 'Meeting',
            'note' => 'Note',
            'task' => 'Task',
            'demo' => 'Demo',
            'proposal' => 'Proposal',
            'contract' => 'Contract',
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'call' => 'heroicon-o-phone',
            'email' => 'heroicon-o-envelope',
            'meeting' => 'heroicon-o-calendar',
            'note' => 'heroicon-o-document-text',
            'task' => 'heroicon-o-check-circle',
            'demo' => 'heroicon-o-presentation-chart-line',
            'proposal' => 'heroicon-o-document-duplicate',
            'contract' => 'heroicon-o-document-check',
            default => 'heroicon-o-chat-bubble-left-right',
        };
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }

    public function getOutcomeBadgeColorAttribute(): ?string
    {
        return match($this->outcome) {
            'positive' => 'success',
            'neutral' => 'warning',
            'negative' => 'danger',
            'follow_up_needed' => 'info',
            default => 'secondary',
        };
    }
}
