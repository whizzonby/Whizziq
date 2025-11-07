<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SocialMediaConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'account_id',
        'account_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'platform_data',
        'is_active',
        'last_synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'platform_data' => 'array',
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Encrypt access token before saving
     */
    public function setAccessTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt access token when retrieving
     */
    public function getAccessTokenAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Encrypt refresh token before saving
     */
    public function setRefreshTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt refresh token when retrieving
     */
    public function getRefreshTokenAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Get platform display name
     */
    public function getPlatformNameAttribute(): string
    {
        return match($this->platform) {
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google_ads' => 'Google Ads',
            'linkedin' => 'LinkedIn Ads',
            'twitter' => 'Twitter/X',
            default => ucfirst($this->platform),
        };
    }

    /**
     * Get platform icon
     */
    public function getPlatformIconAttribute(): string
    {
        return match($this->platform) {
            'facebook' => 'heroicon-o-chat-bubble-left-right',
            'instagram' => 'heroicon-o-camera',
            'google_ads' => 'heroicon-o-magnifying-glass',
            'linkedin' => 'heroicon-o-briefcase',
            'twitter' => 'heroicon-o-at-symbol',
            default => 'heroicon-o-globe-alt',
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->sync_status) {
            'success' => 'success',
            'syncing' => 'info',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Mark sync as started
     */
    public function startSync(): void
    {
        $this->update([
            'sync_status' => 'syncing',
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as successful
     */
    public function markSyncSuccess(): void
    {
        $this->update([
            'sync_status' => 'success',
            'last_synced_at' => now(),
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }

    /**
     * Scope to get active connections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get connections by platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
