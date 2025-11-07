<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShare extends Model
{
    protected $fillable = [
        'document_vault_id',
        'shared_by',
        'shared_with_user_id',
        'permission_level',
        'can_download',
        'can_edit',
        'can_reshare',
        'expires_at',
        'last_accessed_at',
        'access_count',
        'is_active',
    ];

    protected $casts = [
        'can_download' => 'boolean',
        'can_edit' => 'boolean',
        'can_reshare' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count' => 'integer',
    ];

    /**
     * Get the document being shared
     */
    public function documentVault(): BelongsTo
    {
        return $this->belongsTo(DocumentVault::class);
    }

    /**
     * Get the user who shared the document
     */
    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Get the user the document is shared with
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    /**
     * Check if the share has expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if user can access the document
     */
    public function canAccess(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Check if user has permission for an action
     */
    public function hasPermission(string $action): bool
    {
        if (!$this->canAccess()) {
            return false;
        }

        return match($action) {
            'view' => true,
            'download' => $this->can_download || $this->permission_level === 'download' || $this->permission_level === 'edit',
            'edit' => $this->can_edit || $this->permission_level === 'edit',
            'reshare' => $this->can_reshare,
            default => false,
        };
    }

    /**
     * Record an access to the shared document
     */
    public function recordAccess(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Revoke this share
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope to get active shares only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get expired shares
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to get shares for a user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('shared_with_user_id', $userId);
    }

    /**
     * Scope to get shares by a user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('shared_by', $userId);
    }
}
