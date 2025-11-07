<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMetric extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'total_employees',
        'churn_rate',
        'demographics',
        'employee_turnover',
    ];

    protected $casts = [
        'date' => 'date',
        'demographics' => 'array',
        'churn_rate' => 'decimal:2',
        'employee_turnover' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


