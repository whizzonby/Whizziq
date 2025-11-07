<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentActivity extends Model
{
    protected $fillable = [
        'document_vault_id',
        'user_id',
        'activity_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the document this activity belongs to
     */
    public function documentVault(): BelongsTo
    {
        return $this->belongsTo(DocumentVault::class);
    }

    /**
     * Get the user who performed this activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a document activity
     */
    public static function log(
        DocumentVault $document,
        User $user,
        string $activityType,
        string $description,
        ?array $metadata = null
    ): self {
        return static::create([
            'document_vault_id' => $document->id,
            'user_id' => $user->id,
            'activity_type' => $activityType,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get icon for activity type
     */
    public function getActivityIconAttribute(): string
    {
        return match($this->activity_type) {
            'created' => 'heroicon-o-plus-circle',
            'viewed' => 'heroicon-o-eye',
            'downloaded' => 'heroicon-o-arrow-down-tray',
            'edited' => 'heroicon-o-pencil',
            'shared' => 'heroicon-o-share',
            'unshared' => 'heroicon-o-x-circle',
            'version_created' => 'heroicon-o-document-duplicate',
            'version_restored' => 'heroicon-o-arrow-uturn-left',
            'analyzed' => 'heroicon-o-sparkles',
            'deleted' => 'heroicon-o-trash',
            'restored' => 'heroicon-o-arrow-path',
            default => 'heroicon-o-information-circle',
        };
    }

    /**
     * Get color for activity type
     */
    public function getActivityColorAttribute(): string
    {
        return match($this->activity_type) {
            'created' => 'success',
            'viewed' => 'info',
            'downloaded' => 'primary',
            'edited' => 'warning',
            'shared' => 'success',
            'unshared' => 'danger',
            'version_created' => 'info',
            'version_restored' => 'warning',
            'analyzed' => 'primary',
            'deleted' => 'danger',
            'restored' => 'success',
            default => 'gray',
        };
    }

    /**
     * Scope to filter by activity type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope to get activities for a document
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_vault_id', $documentId);
    }

    /**
     * Scope to get activities by a user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
