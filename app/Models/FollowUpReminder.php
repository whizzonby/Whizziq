<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUpReminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'contact_id',
        'deal_id',
        'title',
        'description',
        'remind_at',
        'status',
        'priority',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->pending()
            ->where('remind_at', '<=', now());
    }

    public function scopeDueToday($query)
    {
        return $query->pending()
            ->whereDate('remind_at', today());
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->pending()
            ->whereBetween('remind_at', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue($query)
    {
        return $query->pending()
            ->where('remind_at', '<', now());
    }

    // Accessors
    public function getIsDueAttribute(): bool
    {
        return $this->status === 'pending' && $this->remind_at->isPast();
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->remind_at->isBefore(now()->subHours(24));
    }

    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->remind_at, false);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'pending' => $this->is_overdue ? 'danger' : 'warning',
            'sent' => 'info',
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'gray',
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'yellow',
            'high' => 'red',
            default => 'gray',
        };
    }

    // Business Logic
    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();

        // Log interaction on contact
        $this->contact->logInteraction(
            type: 'task',
            description: "Completed follow-up: {$this->title}",
            subject: $this->title,
            dealId: $this->deal_id
        );
    }

    public function markAsCancelled(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function canSendNotification(): bool
    {
        return $this->status === 'pending' && $this->remind_at->isPast();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($reminder) {
            // Update contact's next follow-up date
            if ($reminder->status === 'pending') {
                $contact = $reminder->contact;
                $earliestReminder = $contact->reminders()
                    ->pending()
                    ->orderBy('remind_at')
                    ->first();

                $contact->next_follow_up_date = $earliestReminder?->remind_at;
                $contact->save();
            }
        });

        static::deleted(function ($reminder) {
            // Update contact's next follow-up date
            $contact = $reminder->contact;
            $earliestReminder = $contact->reminders()
                ->pending()
                ->orderBy('remind_at')
                ->first();

            $contact->next_follow_up_date = $earliestReminder?->remind_at;
            $contact->save();
        });
    }
}
