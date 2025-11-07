<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'meta_keywords',
        'is_published',
        'published_at',
        'author_id',
        'page_type',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            if (empty($page->published_at) && $page->is_published) {
                $page->published_at = now();
            }
        });

        static::updating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            if ($page->is_published && empty($page->published_at)) {
                $page->published_at = now();
            }
        });
    }

    /**
     * Get the author of the page.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope a query to filter by page type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the page URL.
     */
    public function getUrlAttribute(): string
    {
        // Use specific named routes for common pages
        $specificRoutes = [
            'privacy-policy' => 'privacy-policy',
            'terms-of-service' => 'terms-of-service',
        ];

        if (isset($specificRoutes[$this->slug])) {
            return route($specificRoutes[$this->slug]);
        }

        // Use generic page route for other pages
        return route('page.show', $this->slug);
    }

    /**
     * Check if page is policy/legal type.
     */
    public function isPolicyPage(): bool
    {
        return in_array($this->page_type, ['policy', 'legal']);
    }
}
