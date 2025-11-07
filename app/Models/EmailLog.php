<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'user_id',
        'contact_id',
        'email_campaign_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'status',
        'sent_at',
        'error_message',
        'message_id',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
        'tracking_data',
        'email_type',
        'metadata',
        'attachments',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'tracking_data' => 'array',
        'metadata' => 'array',
        'attachments' => 'array',
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

    public function emailCampaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForContact($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePendingSend($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    // Accessors
    public function getWasOpenedAttribute(): bool
    {
        return $this->opened_at !== null;
    }

    public function getWasClickedAttribute(): bool
    {
        return $this->clicked_at !== null;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'sent' => 'success',
            'failed' => 'danger',
            'bounced' => 'gray',
            default => 'gray',
        };
    }

    // Business Logic
    public function markAsSent(?string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'message_id' => $messageId,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function recordOpen(): void
    {
        $this->increment('open_count');

        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }

        // Update campaign stats if part of campaign
        if ($this->email_campaign_id && $this->open_count === 1) {
            $this->emailCampaign?->incrementOpened();
        }
    }

    public function recordClick(): void
    {
        $this->increment('click_count');

        if (!$this->clicked_at) {
            $this->update(['clicked_at' => now()]);
        }

        // Update campaign stats if part of campaign
        if ($this->email_campaign_id && $this->click_count === 1) {
            $this->emailCampaign?->incrementClicked();
        }
    }
}
