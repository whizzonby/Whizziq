<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'risk_score',
        'risk_level',
        'loan_worthiness',
        'loan_worthiness_level',
        'risk_factors',
    ];

    protected $casts = [
        'date' => 'date',
        'risk_score' => 'integer',
        'loan_worthiness' => 'decimal:2',
        'risk_factors' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'success',
            'moderate' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    public function getLoanWorthinessColorAttribute(): string
    {
        return match($this->loan_worthiness_level) {
            'poor' => 'danger',
            'fair' => 'warning',
            'good' => 'success',
            'excellent' => 'success',
            default => 'gray',
        };
    }
}
