<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'category',
        'tags',
        'is_favorite',
        'ai_summary',
        'ai_key_points',
        'ai_analysis',
        'analyzed_at',
        'download_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'ai_analysis' => 'array',
        'is_favorite' => 'boolean',
        'analyzed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Get file URL
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Download file
     */
    public function download()
    {
        $this->increment('download_count');
        $this->update(['last_accessed_at' => now()]);

        return Storage::download($this->file_path, $this->file_name);
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
                Storage::delete($document->file_path);
            }
        });
    }
}
