<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AvailabilityException extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'is_all_day',
        'exception_type',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_all_day' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', now());
    }

    public function scopeOnDate($query, Carbon $date)
    {
        return $query->where(function($q) use ($date) {
            $q->whereDate('start_date', '<=', $date)
              ->whereDate('end_date', '>=', $date);
        });
    }

    // Helper Methods
    public function isActiveOn(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->exception_type) {
            'vacation' => 'Vacation',
            'holiday' => 'Holiday',
            'sick_leave' => 'Sick Leave',
            'personal' => 'Personal Time',
            'other' => 'Other',
            default => 'Exception',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->exception_type) {
            'vacation' => 'blue',
            'holiday' => 'purple',
            'sick_leave' => 'red',
            'personal' => 'yellow',
            'other' => 'gray',
            default => 'gray',
        };
    }
}
