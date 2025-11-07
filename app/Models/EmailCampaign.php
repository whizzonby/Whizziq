<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'email_template_id',
        'name',
        'description',
        'subject',
        'body',
        'recipient_type',
        'recipient_filters',
        'recipient_ids',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'emails_sent',
        'emails_failed',
        'emails_opened',
        'emails_clicked',
        'attachments',
        'from_name',
        'from_email',
        'reply_to',
    ];

    protected $casts = [
        'recipient_filters' => 'array',
        'recipient_ids' => 'array',
        'attachments' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'emails_sent' => 'integer',
        'emails_failed' => 'integer',
        'emails_opened' => 'integer',
        'emails_clicked' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePendingSend($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    // Accessors
    public function getOpenRateAttribute(): float
    {
        if ($this->emails_sent == 0) {
            return 0;
        }

        return round(($this->emails_opened / $this->emails_sent) * 100, 2);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->emails_sent == 0) {
            return 0;
        }

        return round(($this->emails_clicked / $this->emails_sent) * 100, 2);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->emails_sent / $this->total_recipients) * 100, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'scheduled' => 'warning',
            'sending' => 'info',
            'sent' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    // Business Logic
    public function markAsSending(): void
    {
        $this->update(['status' => 'sending']);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function incrementSent(): void
    {
        $this->increment('emails_sent');
    }

    public function incrementFailed(): void
    {
        $this->increment('emails_failed');
    }

    public function incrementOpened(): void
    {
        $this->increment('emails_opened');
    }

    public function incrementClicked(): void
    {
        $this->increment('emails_clicked');
    }
}
