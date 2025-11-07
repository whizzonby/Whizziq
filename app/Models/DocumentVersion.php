<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_vault_id',
        'version_number',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'file_hash',
        'uploaded_by',
        'change_notes',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'file_size' => 'integer',
        'version_number' => 'integer',
    ];

    /**
     * Get the document this version belongs to
     */
    public function documentVault(): BelongsTo
    {
        return $this->belongsTo(DocumentVault::class);
    }

    /**
     * Get the user who uploaded this version
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Download this version
     */
    public function download()
    {
        return Storage::download($this->file_path, $this->file_name);
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Mark this version as current
     */
    public function makeCurrent(): void
    {
        // Mark all other versions as not current
        static::where('document_vault_id', $this->document_vault_id)
            ->update(['is_current' => false]);

        // Mark this version as current
        $this->update(['is_current' => true]);

        // Update the main document with this version's file info
        $this->documentVault->update([
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_hash' => $this->file_hash,
            'version_number' => $this->version_number,
        ]);
    }

    /**
     * Scope to get current versions only
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get versions for a document
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_vault_id', $documentId);
    }
}
