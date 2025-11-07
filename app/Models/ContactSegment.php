<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'filters',
        'color',
        'is_favorite',
        'contact_count',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_favorite' => 'boolean',
        'contact_count' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Apply segment filters to query
    public function applyToQuery($query)
    {
        $filters = $this->filters ?? [];

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['relationship_strength'])) {
            $query->where('relationship_strength', $filters['relationship_strength']);
        }

        if (isset($filters['min_lifetime_value'])) {
            $query->where('lifetime_value', '>=', $filters['min_lifetime_value']);
        }

        if (isset($filters['max_lifetime_value'])) {
            $query->where('lifetime_value', '<=', $filters['max_lifetime_value']);
        }

        if (isset($filters['has_deals'])) {
            if ($filters['has_deals']) {
                $query->where('deals_count', '>', 0);
            } else {
                $query->where('deals_count', 0);
            }
        }

        if (isset($filters['needs_follow_up'])) {
            if ($filters['needs_follow_up']) {
                $query->needsFollowUp();
            }
        }

        if (isset($filters['last_contact_days'])) {
            $days = (int) $filters['last_contact_days'];
            $query->where('last_contact_date', '<=', now()->subDays($days));
        }

        if (isset($filters['tags']) && !empty($filters['tags'])) {
            $query->where('tags', 'like', '%' . $filters['tags'] . '%');
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query;
    }

    // Update contact count for this segment
    public function updateContactCount(): void
    {
        $query = Contact::where('user_id', $this->user_id);
        $query = $this->applyToQuery($query);
        $this->contact_count = $query->count();
        $this->save();
    }
}
