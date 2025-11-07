<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskTag extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'color',
        'icon',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_tag_pivot');
    }

    // Helper Methods
    public function getTaskCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public static function getDefaultTags(): array
    {
        return [
            ['name' => 'Urgent', 'color' => 'danger', 'icon' => 'heroicon-o-exclamation-triangle'],
            ['name' => 'Follow-up', 'color' => 'warning', 'icon' => 'heroicon-o-arrow-path'],
            ['name' => 'Meeting', 'color' => 'primary', 'icon' => 'heroicon-o-calendar'],
            ['name' => 'Email', 'color' => 'info', 'icon' => 'heroicon-o-envelope'],
            ['name' => 'Phone Call', 'color' => 'success', 'icon' => 'heroicon-o-phone'],
            ['name' => 'Review', 'color' => 'purple', 'icon' => 'heroicon-o-eye'],
            ['name' => 'Research', 'color' => 'indigo', 'icon' => 'heroicon-o-magnifying-glass'],
            ['name' => 'Planning', 'color' => 'pink', 'icon' => 'heroicon-o-light-bulb'],
        ];
    }
}
