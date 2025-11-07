<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'contact_id',
        'title',
        'description',
        'stage',
        'value',
        'currency',
        'probability',
        'expected_close_date',
        'actual_close_date',
        'source',
        'priority',
        'loss_reason',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
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

    public function interactions(): HasMany
    {
        return $this->hasMany(ContactInteraction::class)->orderBy('interaction_date', 'desc');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(FollowUpReminder::class)->orderBy('remind_at');
    }

    public function products(): HasMany
    {
        return $this->hasMany(DealProduct::class)->orderBy('sort_order');
    }

    // Methods
    public function updateTotalFromProducts(): void
    {
        $total = $this->products()->sum('line_total');
        $this->value = $total;
        $this->saveQuietly(); // Save without triggering events to avoid recursion
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('stage', ['lead', 'qualified', 'proposal', 'negotiation']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('stage', ['won', 'lost']);
    }

    public function scopeWon($query)
    {
        return $query->where('stage', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('stage', 'lost');
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeClosingSoon($query, int $days = 30)
    {
        return $query->open()
            ->whereBetween('expected_close_date', [now(), now()->addDays($days)]);
    }

    // Accessors
    public function getWeightedValueAttribute(): float
    {
        return ($this->value * $this->probability) / 100;
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->stage, ['won', 'lost']);
    }

    public function getIsWonAttribute(): bool
    {
        return $this->stage === 'won';
    }

    public function getIsLostAttribute(): bool
    {
        return $this->stage === 'lost';
    }

    public function getDaysInStageAttribute(): int
    {
        if (!$this->updated_at) {
            return 0;
        }

        // Ensure we always return a non-negative value
        // Calculate days since the deal was last updated (stage change)
        return abs(now()->diffInDays($this->updated_at));
    }

    public function isStuckInStage(int $thresholdDays = 30): bool
    {
        // A deal is considered "stuck" if it's been in the current stage for more than threshold days
        // and it's not a closed deal (won/lost)
        return !$this->is_closed && $this->days_in_stage > $thresholdDays;
    }

    public function getDaysInStageColorAttribute(): string
    {
        $days = $this->days_in_stage;

        // Color coding based on how long the deal has been in stage
        if ($this->is_closed) {
            return 'gray'; // Closed deals don't need warnings
        }

        if ($days >= 60) {
            return 'danger'; // 60+ days - critical
        }

        if ($days >= 30) {
            return 'warning'; // 30-59 days - warning
        }

        return 'success'; // Less than 30 days - normal
    }

    public function getDaysUntilExpectedCloseAttribute(): ?int
    {
        return $this->expected_close_date
            ? now()->diffInDays($this->expected_close_date, false)
            : null;
    }

    public function getStageColorAttribute(): string
    {
        return match($this->stage) {
            'lead' => 'gray',
            'qualified' => 'blue',
            'proposal' => 'yellow',
            'negotiation' => 'orange',
            'won' => 'success',
            'lost' => 'danger',
            default => 'gray',
        };
    }

    public function getStageLabelAttribute(): string
    {
        return match($this->stage) {
            'lead' => 'Lead',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
            default => ucfirst($this->stage),
        };
    }

    // Business Logic
    public function moveToStage(string $newStage): void
    {
        $this->stage = $newStage;

        // Adjust probability based on stage
        $this->probability = match($newStage) {
            'lead' => 20,
            'qualified' => 40,
            'proposal' => 60,
            'negotiation' => 80,
            'won' => 100,
            'lost' => 0,
            default => $this->probability,
        };

        // Set close date if won or lost
        if (in_array($newStage, ['won', 'lost'])) {
            $this->actual_close_date = now();
        }

        $this->save();

        // Update contact's deal count and lifetime value
        if ($newStage === 'won') {
            $this->contact->increment('deals_count');
            $this->contact->increment('lifetime_value', $this->value);
            $this->contact->convertToClient();
        }
    }

    public function markAsWon(): void
    {
        $this->moveToStage('won');
    }

    public function markAsLost(string $reason): void
    {
        $this->loss_reason = $reason;
        $this->moveToStage('lost');
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($deal) {
            // Set default expected close date if not set
            if (!$deal->expected_close_date) {
                $deal->expected_close_date = now()->addDays(30);
                $deal->save();
            }
        });
    }
}
