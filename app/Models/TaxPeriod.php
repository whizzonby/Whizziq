<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxPeriod extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'filing_deadline',
        'status',
        'closed_at',
        'filed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'filing_deadline' => 'date',
        'closed_at' => 'datetime',
        'filed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxReports(): HasMany
    {
        return $this->hasMany(TaxReport::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isFiled(): bool
    {
        return $this->status === 'filed';
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function markAsFiled(): void
    {
        $this->update([
            'status' => 'filed',
            'filed_at' => now(),
        ]);
    }
}
