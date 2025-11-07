<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'contact_id',
        'appointment_type_id',
        'venue_id',
        'title',
        'description',
        'location',
        'appointment_format',
        'room_name',
        'start_datetime',
        'end_datetime',
        'timezone',
        'status',
        'attendee_name',
        'attendee_email',
        'attendee_phone',
        'attendee_company',
        'notes',
        'cancellation_reason',
        'reminder_sent_at',
        'confirmation_token',
        'booked_via',
        'meeting_platform',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'calendar_event_id',
        'calendar_synced_at',
        'is_recurring',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_days',
        'recurrence_end_date',
        'recurrence_count',
        'recurring_parent_id',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'calendar_synced_at' => 'datetime',
        'recurrence_end_date' => 'date',
        'recurrence_days' => 'array',
        'is_recurring' => 'boolean',
        'meeting_password' => 'encrypted',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function recurringParent(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'recurring_parent_id');
    }

    public function recurringInstances()
    {
        return $this->hasMany(Appointment::class, 'recurring_parent_id');
    }

    // Scopes - CRITICAL FOR PERFORMANCE
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_datetime', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed']);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('start_datetime', today())
            ->whereIn('status', ['scheduled', 'confirmed']);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('start_datetime', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->whereIn('status', ['scheduled', 'confirmed']);
    }

    public function scopeInDateRange(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('start_datetime', [$start, $end]);
    }

    public function scopeByStatus(Builder $query, $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    // Helper Methods
    public function isOnline(): bool
    {
        return $this->appointment_format === 'online' || 
               ($this->appointmentType && $this->appointmentType->appointment_format === 'online');
    }

    public function isInPerson(): bool
    {
        return $this->appointment_format === 'in_person' || 
               ($this->appointmentType && $this->appointmentType->appointment_format === 'in_person');
    }

    public function isHybrid(): bool
    {
        return $this->appointment_format === 'hybrid' || 
               ($this->appointmentType && $this->appointmentType->appointment_format === 'hybrid');
    }

    public function getEffectiveFormat(): string
    {
        return $this->appointment_format ?? $this->appointmentType?->appointment_format ?? 'online';
    }

    public function isUpcoming(): bool
    {
        return $this->start_datetime->isFuture() &&
               in_array($this->status, ['scheduled', 'confirmed']);
    }

    public function isToday(): bool
    {
        return $this->start_datetime->isToday();
    }

    public function isPast(): bool
    {
        return $this->start_datetime->isPast();
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'success',
            'scheduled' => 'warning',
            'completed' => 'info',
            'cancelled' => 'danger',
            'no_show' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'heroicon-s-check-circle',
            'scheduled' => 'heroicon-s-clock',
            'completed' => 'heroicon-s-check-badge',
            'cancelled' => 'heroicon-s-x-circle',
            'no_show' => 'heroicon-s-exclamation-circle',
            default => 'heroicon-s-question-mark-circle',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'no_show' => 'No Show',
            default => Str::title($this->status),
        };
    }

    // Business Logic
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        // Send cancellation notification to attendee
        \Illuminate\Support\Facades\Notification::route('mail', $this->attendee_email)
            ->notify(new \App\Notifications\AppointmentCancelledNotification($this, true));

        // Send cancellation notification to owner
        if ($this->user) {
            $this->user->notify(new \App\Notifications\AppointmentCancelledNotification($this, false));
        }
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsNoShow(): void
    {
        $this->update(['status' => 'no_show']);
    }

    public function reschedule($newStart, $newEnd): void
    {
        $this->update([
            'start_datetime' => $newStart,
            'end_datetime' => $newEnd,
        ]);
    }

    // Recurring Appointment Methods
    public function isRecurringParent(): bool
    {
        return $this->is_recurring && !$this->recurring_parent_id;
    }

    public function isRecurringInstance(): bool
    {
        return (bool) $this->recurring_parent_id;
    }

    public function getRecurrenceDescriptionAttribute(): string
    {
        if (!$this->is_recurring) {
            return 'One-time';
        }

        $description = '';

        switch ($this->recurrence_type) {
            case 'daily':
                $description = $this->recurrence_interval == 1
                    ? 'Daily'
                    : "Every {$this->recurrence_interval} days";
                break;
            case 'weekly':
                $description = $this->recurrence_interval == 1
                    ? 'Weekly'
                    : "Every {$this->recurrence_interval} weeks";

                if ($this->recurrence_days) {
                    $days = collect($this->recurrence_days)->map(function ($day) {
                        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$day];
                    })->implode(', ');
                    $description .= " on {$days}";
                }
                break;
            case 'monthly':
                $description = $this->recurrence_interval == 1
                    ? 'Monthly'
                    : "Every {$this->recurrence_interval} months";
                break;
        }

        if ($this->recurrence_end_date) {
            $description .= " until {$this->recurrence_end_date->format('M d, Y')}";
        } elseif ($this->recurrence_count) {
            $description .= " for {$this->recurrence_count} occurrences";
        }

        return $description;
    }

    // Generate unique confirmation token
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            if (empty($appointment->confirmation_token)) {
                $appointment->confirmation_token = Str::random(64);
            }
        });
    }
}
