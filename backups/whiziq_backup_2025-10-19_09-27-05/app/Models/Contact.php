<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company',
        'job_title',
        'type',
        'status',
        'priority',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'website',
        'linkedin_url',
        'twitter_handle',
        'last_contact_date',
        'next_follow_up_date',
        'relationship_strength',
        'lifetime_value',
        'deals_count',
        'interactions_count',
        'tags',
        'notes',
        'source',
    ];

    protected $casts = [
        'last_contact_date' => 'date',
        'next_follow_up_date' => 'date',
        'lifetime_value' => 'decimal:2',
        'deals_count' => 'integer',
        'interactions_count' => 'integer',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(ContactInteraction::class)->orderBy('interaction_date', 'desc');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(FollowUpReminder::class)->orderBy('remind_at');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeClients($query)
    {
        return $query->where('type', 'client');
    }

    public function scopeLeads($query)
    {
        return $query->where('type', 'lead');
    }

    public function scopePartners($query)
    {
        return $query->where('type', 'partner');
    }

    public function scopeVip($query)
    {
        return $query->where('priority', 'vip');
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->whereNotNull('next_follow_up_date')
            ->where('next_follow_up_date', '<=', now())
            ->where('status', 'active');
    }

    public function scopeFollowUpDueToday($query)
    {
        return $query->whereDate('next_follow_up_date', today())
            ->where('status', 'active');
    }

    public function scopeCold($query)
    {
        return $query->where('relationship_strength', 'cold');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->company ? "{$this->name} - {$this->company}" : $this->name;
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    public function getDaysSinceLastContactAttribute(): ?int
    {
        return $this->last_contact_date
            ? now()->diffInDays($this->last_contact_date)
            : null;
    }

    public function getDaysUntilFollowUpAttribute(): ?int
    {
        if (!$this->next_follow_up_date) {
            return null;
        }

        return now()->diffInDays($this->next_follow_up_date, false);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->next_follow_up_date
            && $this->next_follow_up_date->isPast()
            && $this->status === 'active';
    }

    public function getRelationshipScoreAttribute(): int
    {
        $score = 0;

        // Recent contact bonus
        if ($this->last_contact_date) {
            $daysSince = $this->days_since_last_contact;
            if ($daysSince <= 7) {
                $score += 50;
            } elseif ($daysSince <= 30) {
                $score += 30;
            } elseif ($daysSince <= 90) {
                $score += 10;
            }
        }

        // Interaction frequency
        $score += min($this->interactions_count * 2, 30);

        // Relationship strength
        $score += match($this->relationship_strength) {
            'hot' => 20,
            'warm' => 10,
            'cold' => 0,
            default => 5,
        };

        return min($score, 100);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'client' => 'Client',
            'lead' => 'Lead',
            'partner' => 'Partner',
            'investor' => 'Investor',
            'vendor' => 'Vendor',
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'vip' => 'VIP',
            default => ucfirst($this->priority),
        };
    }

    // Business Logic
    public function logInteraction(
        string $type,
        string $description,
        ?Carbon $interactionDate = null,
        ?string $subject = null,
        ?int $durationMinutes = null,
        ?string $outcome = null,
        ?int $dealId = null
    ): ContactInteraction {
        $interaction = $this->interactions()->create([
            'user_id' => $this->user_id,
            'deal_id' => $dealId,
            'type' => $type,
            'subject' => $subject,
            'description' => $description,
            'interaction_date' => $interactionDate ?? now(),
            'duration_minutes' => $durationMinutes,
            'outcome' => $outcome,
        ]);

        // Update last contact date
        $this->last_contact_date = $interactionDate ?? now();
        $this->interactions_count++;
        $this->save();

        return $interaction;
    }

    public function scheduleFollowUp(
        Carbon $date,
        string $title,
        ?string $description = null,
        string $priority = 'medium',
        ?int $dealId = null
    ): FollowUpReminder {
        $reminder = $this->reminders()->create([
            'user_id' => $this->user_id,
            'deal_id' => $dealId,
            'title' => $title,
            'description' => $description,
            'remind_at' => $date,
            'priority' => $priority,
            'status' => 'pending',
        ]);

        // Update next follow-up date if this is the earliest
        if (!$this->next_follow_up_date || $date->isBefore($this->next_follow_up_date)) {
            $this->next_follow_up_date = $date;
            $this->save();
        }

        return $reminder;
    }

    public function updateRelationshipStrength(): void
    {
        $score = $this->relationship_score;

        if ($score >= 70) {
            $this->relationship_strength = 'hot';
        } elseif ($score >= 40) {
            $this->relationship_strength = 'warm';
        } else {
            $this->relationship_strength = 'cold';
        }

        $this->save();
    }

    public function convertToClient(): void
    {
        $this->type = 'client';
        $this->save();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($contact) {
            // Set initial last contact date if not set
            if (!$contact->last_contact_date) {
                $contact->last_contact_date = now();
                $contact->save();
            }
        });
    }
}
