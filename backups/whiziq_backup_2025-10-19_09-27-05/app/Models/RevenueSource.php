<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'source',
        'amount',
        'percentage',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
