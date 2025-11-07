<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PasswordVault extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'website_url',
        'username',
        'email',
        'encrypted_password',
        'password_last_changed_at',
        'category',
        'notes',
        'is_favorite',
        'is_compromised',
        'compromised_at',
        'needs_update',
        'health_notes',
        'last_accessed_at',
        'access_count',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'is_compromised' => 'boolean',
        'needs_update' => 'boolean',
        'password_last_changed_at' => 'datetime',
        'compromised_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count' => 'integer',
    ];

    protected $hidden = [
        'encrypted_password',
    ];

    /**
     * Get the user that owns the password vault entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the audit logs for this password vault entry
     */
    public function auditLogs()
    {
        return $this->hasMany(PasswordVaultAuditLog::class);
    }

    /**
     * Set the password (encrypts it)
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['encrypted_password'] = Crypt::encryptString($value);
            $this->attributes['password_last_changed_at'] = now();
        }
    }

    /**
     * Get the decrypted password
     */
    public function getPasswordAttribute(): ?string
    {
        if (isset($this->attributes['encrypted_password'])) {
            try {
                return Crypt::decryptString($this->attributes['encrypted_password']);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Track when password is accessed
     */
    public function trackAccess(string $action = 'viewed'): void
    {
        $this->update([
            'last_accessed_at' => now(),
            'access_count' => $this->access_count + 1,
        ]);

        // Log the audit action
        PasswordVaultAuditLog::logAction($this, $action);
    }

    /**
     * Get category icon
     */
    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'social_media' => 'heroicon-o-hashtag',
            'email' => 'heroicon-o-envelope',
            'banking' => 'heroicon-o-banknotes',
            'work' => 'heroicon-o-briefcase',
            'personal' => 'heroicon-o-user',
            'entertainment' => 'heroicon-o-film',
            'shopping' => 'heroicon-o-shopping-cart',
            'development' => 'heroicon-o-code-bracket',
            default => 'heroicon-o-key',
        };
    }

    /**
     * Get category color
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'social_media' => 'info',
            'email' => 'success',
            'banking' => 'warning',
            'work' => 'primary',
            'personal' => 'gray',
            'entertainment' => 'danger',
            'shopping' => 'purple',
            'development' => 'cyan',
            default => 'gray',
        };
    }

    /**
     * Scope to only favorites
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get password strength (weak, medium, strong)
     */
    public function getPasswordStrengthAttribute(): string
    {
        $password = $this->password;

        if (!$password) {
            return 'unknown';
        }

        $length = strlen($password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

        $strength = 0;
        if ($length >= 8) $strength++;
        if ($length >= 12) $strength++;
        if ($hasUpper) $strength++;
        if ($hasLower) $strength++;
        if ($hasNumber) $strength++;
        if ($hasSpecial) $strength++;

        if ($strength <= 2) return 'weak';
        if ($strength <= 4) return 'medium';
        return 'strong';
    }

    /**
     * Get password strength color
     */
    public function getPasswordStrengthColorAttribute(): string
    {
        return match ($this->password_strength) {
            'weak' => 'danger',
            'medium' => 'warning',
            'strong' => 'success',
            default => 'gray',
        };
    }

    /**
     * Check if password is old (needs updating)
     */
    public function getIsPasswordOldAttribute(): bool
    {
        if (!$this->password_last_changed_at) {
            return true; // Never changed = old
        }

        return $this->password_last_changed_at->diffInDays(now()) > 90;
    }

    /**
     * Get password age in days
     */
    public function getPasswordAgeDaysAttribute(): int
    {
        if (!$this->password_last_changed_at) {
            return $this->created_at?->diffInDays(now()) ?? 0;
        }

        return $this->password_last_changed_at->diffInDays(now());
    }

    /**
     * Get password health status
     */
    public function getPasswordHealthAttribute(): string
    {
        if ($this->is_compromised) {
            return 'compromised';
        }

        if ($this->needs_update) {
            return 'update_needed';
        }

        if ($this->password_strength === 'weak') {
            return 'weak';
        }

        if ($this->is_password_old) {
            return 'old';
        }

        if ($this->password_strength === 'medium') {
            return 'fair';
        }

        return 'good';
    }

    /**
     * Get password health color
     */
    public function getPasswordHealthColorAttribute(): string
    {
        return match ($this->password_health) {
            'compromised' => 'danger',
            'update_needed' => 'danger',
            'weak' => 'danger',
            'old' => 'warning',
            'fair' => 'warning',
            'good' => 'success',
            default => 'gray',
        };
    }

    /**
     * Mark password as compromised
     */
    public function markAsCompromised(string $reason = null): void
    {
        $this->update([
            'is_compromised' => true,
            'compromised_at' => now(),
            'needs_update' => true,
            'health_notes' => $reason,
        ]);

        PasswordVaultAuditLog::logAction($this, 'marked_compromised', [
            'reason' => $reason,
        ]);
    }

    /**
     * Scope to get passwords needing update
     */
    public function scopeNeedsUpdate($query)
    {
        return $query->where(function ($q) {
            $q->where('needs_update', true)
              ->orWhere('is_compromised', true)
              ->orWhereRaw('password_last_changed_at < ?', [now()->subDays(90)]);
        });
    }

    /**
     * Scope to get weak passwords
     */
    public function scopeWeakPasswords($query)
    {
        // This is a simplified scope - actual implementation would need to decrypt
        // and check strength, which is expensive. Better to use a scheduled command.
        return $query->where('needs_update', true);
    }

    /**
     * Scope to get compromised passwords
     */
    public function scopeCompromised($query)
    {
        return $query->where('is_compromised', true);
    }
}
