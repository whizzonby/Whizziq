<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DocumentVault extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'file_hash',
        'category',
        'tags',
        'is_favorite',
        'ai_summary',
        'ai_key_points',
        'ai_analysis',
        'extracted_text',
        'analyzed_at',
        'download_count',
        'last_accessed_at',
        'expires_at',
        'retention_policy',
        'version_number',
    ];

    protected $casts = [
        'tags' => 'array',
        'ai_analysis' => 'array',
        'is_favorite' => 'boolean',
        'analyzed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'expires_at' => 'datetime',
        'version_number' => 'integer',
    ];

    /**
     * Get the user that owns the document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all versions of this document
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get current version
     */
    public function currentVersion()
    {
        return $this->hasOne(DocumentVersion::class)->where('is_current', true);
    }

    /**
     * Get all shares for this document
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * Get active shares
     */
    public function activeShares(): HasMany
    {
        return $this->hasMany(DocumentShare::class)->active();
    }

    /**
     * Get all activities for this document
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file URL (for public disk)
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Download file
     */
    public function download()
    {
        $this->increment('download_count');
        $this->update(['last_accessed_at' => now()]);

        // Log activity
        DocumentActivity::log(
            $this,
            auth()->user(),
            'downloaded',
            'Document downloaded'
        );

        return Storage::disk('public')->download($this->file_path, $this->file_name);
    }

    /**
     * Create a new version of this document
     */
    public function createVersion(string $filePath, string $fileName, array $fileInfo, ?string $changeNotes = null): DocumentVersion
    {
        // Increment version number
        $newVersionNumber = $this->version_number + 1;

        // Mark all existing versions as not current
        $this->versions()->update(['is_current' => false]);

        // Create new version
        $version = $this->versions()->create([
            'version_number' => $newVersionNumber,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileInfo['file_type'],
            'mime_type' => $fileInfo['mime_type'],
            'file_size' => $fileInfo['file_size'],
            'file_hash' => $fileInfo['file_hash'],
            'uploaded_by' => auth()->id(),
            'change_notes' => $changeNotes,
            'is_current' => true,
        ]);

        // Update main document
        $this->update([
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileInfo['file_type'],
            'mime_type' => $fileInfo['mime_type'],
            'file_size' => $fileInfo['file_size'],
            'file_hash' => $fileInfo['file_hash'],
            'version_number' => $newVersionNumber,
        ]);

        // Log activity
        DocumentActivity::log(
            $this,
            auth()->user(),
            'version_created',
            "Version {$newVersionNumber} created" . ($changeNotes ? ": {$changeNotes}" : ''),
            ['version_number' => $newVersionNumber]
        );

        return $version;
    }

    /**
     * Share document with a user
     */
    public function shareWith(
        User $user,
        string $permissionLevel = 'view',
        ?array $options = []
    ): DocumentShare {
        $share = $this->shares()->create([
            'shared_by' => auth()->id(),
            'shared_with_user_id' => $user->id,
            'permission_level' => $permissionLevel,
            'can_download' => $options['can_download'] ?? false,
            'can_edit' => $options['can_edit'] ?? false,
            'can_reshare' => $options['can_reshare'] ?? false,
            'expires_at' => $options['expires_at'] ?? null,
        ]);

        // Log activity
        DocumentActivity::log(
            $this,
            auth()->user(),
            'shared',
            "Document shared with {$user->name}",
            ['shared_with' => $user->id, 'permission_level' => $permissionLevel]
        );

        return $share;
    }

    /**
     * Check if document is shared with a user
     */
    public function isSharedWith(User $user): bool
    {
        return $this->activeShares()->where('shared_with_user_id', $user->id)->exists();
    }

    /**
     * Get user's permission for this document
     */
    public function getPermissionFor(User $user): ?DocumentShare
    {
        return $this->activeShares()
            ->where('shared_with_user_id', $user->id)
            ->first();
    }

    /**
     * Log an activity for this document
     */
    public function logActivity(string $activityType, string $description, ?array $metadata = null): DocumentActivity
    {
        return DocumentActivity::log($this, auth()->user(), $activityType, $description, $metadata);
    }

    /**
     * Check if document has expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check for duplicate document by hash
     */
    public static function findDuplicateByHash(string $hash, int $userId): ?self
    {
        return static::where('file_hash', $hash)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get file icon based on type
     */
    public function getFileIconAttribute(): string
    {
        return match ($this->file_type) {
            'pdf' => 'heroicon-o-document-text',
            'doc', 'docx' => 'heroicon-o-document',
            'xls', 'xlsx' => 'heroicon-o-table-cells',
            'ppt', 'pptx' => 'heroicon-o-presentation-chart-bar',
            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'heroicon-o-photo',
            'zip', 'rar', '7z' => 'heroicon-o-archive-box',
            'mp4', 'avi', 'mov' => 'heroicon-o-film',
            'mp3', 'wav', 'ogg' => 'heroicon-o-musical-note',
            'txt' => 'heroicon-o-document-text',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Get file color based on type
     */
    public function getFileColorAttribute(): string
    {
        return match ($this->file_type) {
            'pdf' => 'danger',
            'doc', 'docx' => 'primary',
            'xls', 'xlsx' => 'success',
            'ppt', 'pptx' => 'warning',
            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'info',
            'zip', 'rar', '7z' => 'gray',
            'mp4', 'avi', 'mov' => 'purple',
            'mp3', 'wav', 'ogg' => 'cyan',
            default => 'gray',
        };
    }

    /**
     * Get category icon
     */
    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'legal' => 'heroicon-o-scale',
            'financial' => 'heroicon-o-banknotes',
            'business' => 'heroicon-o-briefcase',
            'personal' => 'heroicon-o-user',
            'education' => 'heroicon-o-academic-cap',
            'medical' => 'heroicon-o-heart',
            'contract' => 'heroicon-o-document-check',
            'invoice' => 'heroicon-o-receipt-percent',
            'report' => 'heroicon-o-chart-bar',
            'proposal' => 'heroicon-o-light-bulb',
            default => 'heroicon-o-folder',
        };
    }

    /**
     * Get category color
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'legal' => 'warning',
            'financial' => 'success',
            'business' => 'primary',
            'personal' => 'gray',
            'education' => 'info',
            'medical' => 'danger',
            'contract' => 'purple',
            'invoice' => 'cyan',
            'report' => 'indigo',
            'proposal' => 'amber',
            default => 'gray',
        };
    }

    /**
     * Check if document has been analyzed
     */
    public function isAnalyzed(): bool
    {
        return $this->analyzed_at !== null;
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return in_array($this->file_type, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'pdf';
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        return in_array($this->file_type, ['pdf', 'doc', 'docx', 'txt']);
    }

    /**
     * Check if file is a spreadsheet
     */
    public function isSpreadsheet(): bool
    {
        return in_array($this->file_type, ['xls', 'xlsx', 'csv']);
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
     * Scope by file type
     */
    public function scopeByFileType($query, string $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    /**
     * Scope for recently accessed
     */
    public function scopeRecentlyAccessed($query, int $days = 7)
    {
        return $query->where('last_accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($document) {
            if ($document->isForceDeleting()) {
                Storage::disk('public')->delete($document->file_path);
            }
        });
    }
}
