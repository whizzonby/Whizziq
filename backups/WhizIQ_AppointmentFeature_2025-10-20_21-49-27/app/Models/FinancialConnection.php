<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class FinancialConnection extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'sync_status',
        'last_synced_at',
        'last_error',
        'account_id',
        'account_name',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Encrypt access tokens
    public function setAccessTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        }
    }

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

    // Encrypt refresh tokens
    public function setRefreshTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

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

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function markSyncSuccess(): void
    {
        $this->update([
            'sync_status' => 'success',
            'last_synced_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'last_error' => $error,
        ]);
    }

    public function startSync(): void
    {
        $this->update(['sync_status' => 'syncing']);
    }

    // Accessor for platform name
    public function getPlatformNameAttribute(): string
    {
        return match($this->platform) {
            'quickbooks' => 'QuickBooks',
            'xero' => 'Xero',
            'stripe' => 'Stripe',
            'oracle' => 'Oracle Financials',
            'sap' => 'SAP',
            default => ucfirst($this->platform),
        };
    }

    // Accessor for status color
    public function getStatusColorAttribute(): string
    {
        return match($this->sync_status) {
            'success' => 'success',
            'syncing' => 'info',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}
