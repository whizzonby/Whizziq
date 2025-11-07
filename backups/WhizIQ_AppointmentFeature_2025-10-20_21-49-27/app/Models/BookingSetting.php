<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingSetting extends Model
{
    protected $fillable = [
        'user_id',
        'booking_slug',
        'is_booking_enabled',
        'display_name',
        'welcome_message',
        'logo_url',
        'brand_color',
        'timezone',
        'require_approval',
        'min_booking_notice_hours',
        'max_booking_days_ahead',
        'notify_email',
        'notify_sms',
        'send_reminder_hours_before',
    ];

    protected $casts = [
        'is_booking_enabled' => 'boolean',
        'require_approval' => 'boolean',
        'min_booking_notice_hours' => 'integer',
        'max_booking_days_ahead' => 'integer',
        'send_reminder_hours_before' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods
    public function getBookingUrlAttribute(): string
    {
        return url('book/' . $this->booking_slug);
    }

    public function isWithinBookingWindow(\Carbon\Carbon $date): bool
    {
        $minDate = now()->addHours($this->min_booking_notice_hours);
        $maxDate = now()->addDays($this->max_booking_days_ahead);

        return $date->between($minDate, $maxDate);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($settings) {
            if (empty($settings->booking_slug)) {
                $user = $settings->user ?? \App\Models\User::find($settings->user_id);
                $baseSlug = Str::slug($user->name);
                $slug = $baseSlug;
                $counter = 1;

                while (self::where('booking_slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $settings->booking_slug = $slug;
            }
        });

        static::created(function ($settings) {
            // Automatically create default availability schedule (Mon-Fri, 9 AM - 5 PM)
            // Only if no schedule exists yet
            $hasSchedule = AvailabilitySchedule::where('user_id', $settings->user_id)->exists();
            if (!$hasSchedule) {
                $service = new \App\Services\AvailabilityService();
                $service->createDefaultSchedule($settings->user_id);
            }
        });
    }
}
