<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AppointmentType extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'is_active',
        'color',
        'buffer_before_minutes',
        'buffer_after_minutes',
        'max_per_day',
        'require_phone',
        'require_company',
        'custom_questions',
        'sort_order',
        'appointment_format',
        'default_venue_id',
        'requires_location',
        'allowed_venues',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'require_phone' => 'boolean',
        'require_company' => 'boolean',
        'requires_location' => 'boolean',
        'custom_questions' => 'array',
        'allowed_venues' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function defaultVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'default_venue_id');
    }

    // Scopes
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper Methods
    public function getTotalDurationMinutesAttribute(): int
    {
        return $this->duration_minutes +
               $this->buffer_before_minutes +
               $this->buffer_after_minutes;
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->price > 0 ? '$' . number_format($this->price, 2) : 'Free';
    }

    public function hasReachedDailyLimit($date): bool
    {
        if (!$this->max_per_day) {
            return false;
        }

        $count = $this->appointments()
            ->whereDate('start_datetime', $date)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();

        return $count >= $this->max_per_day;
    }

    public function isOnlineFormat(): bool
    {
        return $this->appointment_format === 'online';
    }

    public function isInPersonFormat(): bool
    {
        return $this->appointment_format === 'in_person';
    }

    public function isHybridFormat(): bool
    {
        return $this->appointment_format === 'hybrid';
    }

    public function isPhoneFormat(): bool
    {
        return $this->appointment_format === 'phone';
    }

    public function requiresVenue(): bool
    {
        return in_array($this->appointment_format, ['in_person', 'hybrid']) || $this->requires_location;
    }
}
