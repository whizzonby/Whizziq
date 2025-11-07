<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwotAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStrengths($query)
    {
        return $query->where('type', 'strength');
    }

    public function scopeWeaknesses($query)
    {
        return $query->where('type', 'weakness');
    }

    public function scopeOpportunities($query)
    {
        return $query->where('type', 'opportunity');
    }

    public function scopeThreats($query)
    {
        return $query->where('type', 'threat');
    }
}
