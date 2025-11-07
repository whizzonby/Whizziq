<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'subject',
        'body',
        'category',
        'is_active',
        'is_default',
        'times_used',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'times_used' => 'integer',
        'last_used_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Helper Methods
    public function renderWithVariables(array $variables): array
    {
        $subject = $this->replaceVariables($this->subject, $variables);
        $body = $this->replaceVariables($this->body, $variables);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value ?? '', $content);
        }

        return $content;
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
        $this->update(['last_used_at' => now()]);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'follow_up' => 'Follow Up',
            'welcome' => 'Welcome',
            'appointment_reminder' => 'Appointment Reminder',
            'marketing' => 'Marketing',
            'other' => 'Other',
            default => Str::title($this->category),
        };
    }

    // Available template variables
    public static function getAvailableVariables(): array
    {
        return [
            // Basic Contact Info
            'name' => 'Contact Name',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'company' => 'Company Name',
            'job_title' => 'Job Title',

            // Advanced Contact Info
            'address' => 'Full Address',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'website' => 'Website',

            // Appointment Info
            'next_appointment_date' => 'Next Appointment Date',
            'next_appointment_time' => 'Next Appointment Time',
            'appointment_type' => 'Appointment Type',

            // Relationship Info
            'last_contact_date' => 'Last Contact Date',
            'days_since_last_contact' => 'Days Since Last Contact',
            'relationship_strength' => 'Relationship Strength',
            'lifetime_value' => 'Lifetime Value',

            // Business Owner Info
            'owner_name' => 'Your Name',
            'owner_email' => 'Your Email',
            'owner_phone' => 'Your Phone',
            'owner_company' => 'Your Company',

            // System
            'current_date' => 'Current Date',
            'current_year' => 'Current Year',
        ];
    }
}
