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
        'category',
        'notes',
        'is_favorite',
        'last_accessed_at',
        'access_count',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
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
     * Set the password (encrypts it)
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['encrypted_password'] = Crypt::encryptString($value);
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
    public function trackAccess(): void
    {
        $this->update([
            'last_accessed_at' => now(),
            'access_count' => $this->access_count + 1,
        ]);
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
}
