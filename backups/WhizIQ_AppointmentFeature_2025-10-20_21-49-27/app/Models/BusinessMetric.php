<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'revenue',
        'profit',
        'expenses',
        'cash_flow',
        'revenue_change_percentage',
        'profit_change_percentage',
        'expenses_change_percentage',
        'cash_flow_change_percentage',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue' => 'decimal:2',
        'profit' => 'decimal:2',
        'expenses' => 'decimal:2',
        'cash_flow' => 'decimal:2',
        'revenue_change_percentage' => 'decimal:2',
        'profit_change_percentage' => 'decimal:2',
        'expenses_change_percentage' => 'decimal:2',
        'cash_flow_change_percentage' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
