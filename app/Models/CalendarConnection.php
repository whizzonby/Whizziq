<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CalendarConnection extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_primary',
        'sync_enabled',
        'last_synced_at',
        'calendar_id',
        'calendar_timezone',
        'sync_token',
        'calendar_name',
        'scopes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sync_enabled' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'scopes' => 'array',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'sync_token' => 'encrypted',
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

    // Scopes

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeSyncEnabled($query)
    {
        return $query->where('sync_enabled', true);
    }

    public function scopeGoogle($query)
    {
        return $query->where('provider', 'google_calendar');
    }

    public function scopeZoom($query)
    {
        return $query->where('provider', 'zoom');
    }

    public function scopeNeedingSync($query)
    {
        return $query->where('sync_enabled', true)
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subMinutes(15));
            });
    }

    // Helper Methods

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        // Consider token expired if it expires in less than 5 minutes
        return $this->token_expires_at->isBefore(now()->addMinutes(5));
    }

    public function needsTokenRefresh(): bool
    {
        return $this->isTokenExpired() && $this->refresh_token;
    }

    public function canSync(): bool
    {
        return $this->sync_enabled &&
               !$this->isTokenExpired() &&
               $this->access_token;
    }

    public function markAsSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
        ]);
    }

    public function getProviderLabel(): string
    {
        return match($this->provider) {
            'google_calendar' => 'Google Calendar',
            'outlook' => 'Microsoft Outlook',
            'apple_calendar' => 'Apple iCloud',
            'zoom' => 'Zoom',
            default => ucfirst($this->provider),
        };
    }
}
