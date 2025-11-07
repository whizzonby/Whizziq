<?php

namespace App\Services;

use App\Models\CalendarConnection;
use App\Models\User;
use App\Services\Calendar\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalendarSyncService
{
    protected GoogleCalendarService $googleCalendarService;

    public function __construct()
    {
        $this->googleCalendarService = new GoogleCalendarService();
    }

    /**
     * Sync all active calendar connections for a user
     */
    public function syncUserCalendars(User $user, bool $fullSync = false): array
    {
        $connections = CalendarConnection::where('user_id', $user->id)
            ->syncEnabled()
            ->get();

        $results = [
            'synced' => 0,
            'failed' => 0,
            'events_fetched' => 0,
        ];

        foreach ($connections as $connection) {
            try {
                $eventCount = $this->syncConnection($connection, $fullSync);
                $results['synced']++;
                $results['events_fetched'] += $eventCount;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Calendar sync failed for connection', [
                    'connection_id' => $connection->id,
                    'provider' => $connection->provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Sync a specific calendar connection
     */
    public function syncConnection(CalendarConnection $connection, bool $fullSync = false): int
    {
        if (!$connection->canSync()) {
            Log::warning('Connection cannot sync', [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
            ]);
            return 0;
        }

        return match($connection->provider) {
            'google_calendar' => $this->syncGoogleCalendar($connection, $fullSync),
            // Add other providers here (outlook, apple_calendar, etc.)
            default => 0,
        };
    }

    /**
     * Sync Google Calendar events
     */
    protected function syncGoogleCalendar(CalendarConnection $connection, bool $fullSync = false): int
    {
        if (!$this->googleCalendarService->isConfigured()) {
            Log::warning('Google Calendar service not configured');
            return 0;
        }

        $options = [];
        if ($fullSync) {
            $options['fullSync'] = true;
        }

        $events = $this->googleCalendarService->listEvents($connection, $options);

        if ($events === null) {
            throw new \Exception('Failed to fetch events from Google Calendar');
        }

        // Process and cache the events
        $busyTimes = $this->processBusyTimes($events);

        // Cache busy times for quick availability checks
        $cacheKey = "calendar_busy_times_{$connection->user_id}_{$connection->id}";
        Cache::put($cacheKey, $busyTimes, now()->addHours(1));

        // Update last synced timestamp
        $connection->markAsSynced();

        Log::info('Google Calendar synced successfully', [
            'connection_id' => $connection->id,
            'events_count' => count($events),
            'busy_times_count' => count($busyTimes),
        ]);

        return count($events);
    }

    /**
     * Process calendar events to extract busy times
     */
    protected function processBusyTimes(array $events): array
    {
        $busyTimes = [];

        foreach ($events as $event) {
            // Skip cancelled events
            if (isset($event['status']) && $event['status'] === 'cancelled') {
                continue;
            }

            // Skip all-day events (they don't block specific time slots)
            if (isset($event['start']['date'])) {
                continue;
            }

            // Only include events where user is busy
            $transparency = $event['transparency'] ?? 'opaque';
            if ($transparency === 'transparent') {
                continue; // User marked as available during this event
            }

            // Get start and end times
            $start = $event['start']['dateTime'] ?? null;
            $end = $event['end']['dateTime'] ?? null;

            if (!$start || !$end) {
                continue;
            }

            try {
                $busyTimes[] = [
                    'start' => Carbon::parse($start),
                    'end' => Carbon::parse($end),
                    'event_id' => $event['id'] ?? null,
                    'summary' => $event['summary'] ?? 'Busy',
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to parse event time', [
                    'event_id' => $event['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Sort by start time
        usort($busyTimes, function ($a, $b) {
            return $a['start']->timestamp <=> $b['start']->timestamp;
        });

        return $busyTimes;
    }

    /**
     * Get busy times for a user on a specific date
     */
    public function getUserBusyTimes(User $user, Carbon $date): Collection
    {
        $connections = CalendarConnection::where('user_id', $user->id)
            ->syncEnabled()
            ->get();

        $allBusyTimes = collect();

        foreach ($connections as $connection) {
            $cacheKey = "calendar_busy_times_{$user->id}_{$connection->id}";
            $busyTimes = Cache::get($cacheKey, []);

            foreach ($busyTimes as $busyTime) {
                // Filter busy times for the requested date
                if ($busyTime['start']->isSameDay($date)) {
                    $allBusyTimes->push($busyTime);
                }
            }
        }

        return $allBusyTimes->sortBy('start')->values();
    }

    /**
     * Get busy times for a user in a date range
     */
    public function getUserBusyTimesInRange(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $connections = CalendarConnection::where('user_id', $user->id)
            ->syncEnabled()
            ->get();

        $allBusyTimes = collect();

        foreach ($connections as $connection) {
            $cacheKey = "calendar_busy_times_{$user->id}_{$connection->id}";
            $busyTimes = Cache::get($cacheKey, []);

            foreach ($busyTimes as $busyTime) {
                // Check if busy time overlaps with date range
                if ($this->overlapsDateRange($busyTime['start'], $busyTime['end'], $startDate, $endDate)) {
                    $allBusyTimes->push($busyTime);
                }
            }
        }

        return $allBusyTimes->sortBy('start')->values();
    }

    /**
     * Check if a time slot is available for a user
     */
    public function isTimeSlotAvailable(User $user, Carbon $startTime, Carbon $endTime): bool
    {
        $connections = CalendarConnection::where('user_id', $user->id)
            ->syncEnabled()
            ->get();

        foreach ($connections as $connection) {
            $cacheKey = "calendar_busy_times_{$user->id}_{$connection->id}";
            $busyTimes = Cache::get($cacheKey, []);

            foreach ($busyTimes as $busyTime) {
                // Check if requested time overlaps with busy time
                if ($this->timesOverlap($startTime, $endTime, $busyTime['start'], $busyTime['end'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get all busy times for a specific day (PERFORMANCE OPTIMIZED)
     */
    public function getBusyTimesForDay(User $user, Carbon $date): array
    {
        $connections = CalendarConnection::where('user_id', $user->id)
            ->syncEnabled()
            ->get();

        $allBusyTimes = [];
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        foreach ($connections as $connection) {
            $cacheKey = "calendar_busy_times_{$user->id}_{$connection->id}";
            $busyTimes = Cache::get($cacheKey, []);

            foreach ($busyTimes as $busyTime) {
                $busyStart = Carbon::parse($busyTime['start']);
                $busyEnd = Carbon::parse($busyTime['end']);

                // Only include busy times that overlap with the requested day
                if ($busyStart->lt($dayEnd) && $busyEnd->gt($dayStart)) {
                    $allBusyTimes[] = [
                        'start' => $busyStart,
                        'end' => $busyEnd,
                    ];
                }
            }
        }

        return $allBusyTimes;
    }

    /**
     * Check if two time ranges overlap
     */
    protected function timesOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool
    {
        return $start1->lt($end2) && $end1->gt($start2);
    }

    /**
     * Check if a busy time overlaps with a date range
     */
    protected function overlapsDateRange(Carbon $busyStart, Carbon $busyEnd, Carbon $rangeStart, Carbon $rangeEnd): bool
    {
        return $busyStart->lt($rangeEnd) && $busyEnd->gt($rangeStart);
    }

    /**
     * Force sync all connections that need syncing
     */
    public function syncAllPendingConnections(): array
    {
        $connections = CalendarConnection::needingSync()->get();

        $results = [
            'total' => $connections->count(),
            'synced' => 0,
            'failed' => 0,
            'events_fetched' => 0,
        ];

        foreach ($connections as $connection) {
            try {
                $eventCount = $this->syncConnection($connection);
                $results['synced']++;
                $results['events_fetched'] += $eventCount;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to sync connection', [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Clear cached busy times for a user
     */
    public function clearUserCache(User $user): void
    {
        $connections = CalendarConnection::where('user_id', $user->id)->get();

        foreach ($connections as $connection) {
            $cacheKey = "calendar_busy_times_{$user->id}_{$connection->id}";
            Cache::forget($cacheKey);
        }

        Log::info('Cleared calendar cache for user', ['user_id' => $user->id]);
    }

    /**
     * Get sync status for a user's connections
     */
    public function getUserSyncStatus(User $user): array
    {
        $connections = CalendarConnection::where('user_id', $user->id)->get();

        $status = [
            'total_connections' => $connections->count(),
            'enabled' => $connections->where('sync_enabled', true)->count(),
            'disabled' => $connections->where('sync_enabled', false)->count(),
            'connections' => [],
        ];

        foreach ($connections as $connection) {
            $status['connections'][] = [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'email' => $connection->provider_email,
                'is_primary' => $connection->is_primary,
                'sync_enabled' => $connection->sync_enabled,
                'last_synced_at' => $connection->last_synced_at?->diffForHumans(),
                'token_expires_at' => $connection->token_expires_at?->diffForHumans(),
                'needs_refresh' => $connection->needsTokenRefresh(),
            ];
        }

        return $status;
    }

    /**
     * Push a WhizIQ appointment to Google Calendar (TWO-WAY SYNC)
     */
    public function pushAppointmentToCalendar($appointment, bool $forceCreate = false): array
    {
        $user = $appointment->user;

        // Get primary Google Calendar connection
        $connection = CalendarConnection::where('user_id', $user->id)
            ->where('provider', 'google_calendar')
            ->where('is_primary', true)
            ->where('sync_enabled', true)
            ->first();

        if (!$connection) {
            Log::info('No primary Google Calendar connection found for push', [
                'user_id' => $user->id,
                'appointment_id' => $appointment->id,
            ]);
            return [
                'success' => false,
                'message' => 'No active Google Calendar connection',
            ];
        }

        // Check for conflicts before pushing
        $conflicts = $this->detectConflicts($user, $appointment->start_datetime, $appointment->end_datetime, $appointment->id);

        if (!empty($conflicts) && !$forceCreate) {
            Log::warning('Conflicts detected when pushing appointment', [
                'appointment_id' => $appointment->id,
                'conflicts_count' => count($conflicts),
            ]);

            return [
                'success' => false,
                'message' => 'Scheduling conflicts detected',
                'conflicts' => $conflicts,
            ];
        }

        // Prepare event data
        $eventData = [
            'summary' => $appointment->title,
            'description' => $this->buildAppointmentDescription($appointment),
            'start_time' => $appointment->start_datetime->toRfc3339String(),
            'end_time' => $appointment->end_datetime->toRfc3339String(),
            'timezone' => $appointment->timezone ?? config('app.timezone'),
            'attendees' => $appointment->attendee_email ? [$appointment->attendee_email] : [],
            'add_meet' => false, // Don't add Meet since we may have Zoom or other platform
        ];

        try {
            // Check if appointment already has a calendar event ID (update vs create)
            if ($appointment->calendar_event_id && !$forceCreate) {
                // Update existing event
                $success = $this->googleCalendarService->updateEvent(
                    $connection,
                    $appointment->calendar_event_id,
                    $eventData
                );

                if ($success) {
                    Log::info('Updated appointment in Google Calendar', [
                        'appointment_id' => $appointment->id,
                        'event_id' => $appointment->calendar_event_id,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Appointment updated in Google Calendar',
                        'event_id' => $appointment->calendar_event_id,
                    ];
                } else {
                    // If update fails, try to create new event
                    Log::warning('Update failed, creating new event', [
                        'appointment_id' => $appointment->id,
                    ]);
                }
            }

            // Create new event
            $result = $this->googleCalendarService->createEvent($connection, $eventData);

            if ($result) {
                // Store the calendar event ID for future updates
                $appointment->update([
                    'calendar_event_id' => $result['id'],
                    'calendar_synced_at' => now(),
                ]);

                Log::info('Created appointment in Google Calendar', [
                    'appointment_id' => $appointment->id,
                    'event_id' => $result['id'],
                ]);

                // Clear cached busy times to include new event
                $this->clearUserCache($user);

                return [
                    'success' => true,
                    'message' => 'Appointment synced to Google Calendar',
                    'event_id' => $result['id'],
                    'event_link' => $result['html_link'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create event in Google Calendar',
            ];
        } catch (\Exception $e) {
            Log::error('Error pushing appointment to Google Calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error syncing to calendar: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete appointment from Google Calendar
     */
    public function deleteAppointmentFromCalendar($appointment): bool
    {
        if (!$appointment->calendar_event_id) {
            return true; // Nothing to delete
        }

        $user = $appointment->user;

        $connection = CalendarConnection::where('user_id', $user->id)
            ->where('provider', 'google_calendar')
            ->where('is_primary', true)
            ->first();

        if (!$connection) {
            return false;
        }

        try {
            $success = $this->googleCalendarService->deleteEvent(
                $connection,
                $appointment->calendar_event_id
            );

            if ($success) {
                $appointment->update([
                    'calendar_event_id' => null,
                    'calendar_synced_at' => null,
                ]);

                // Clear cached busy times
                $this->clearUserCache($user);

                Log::info('Deleted appointment from Google Calendar', [
                    'appointment_id' => $appointment->id,
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Error deleting appointment from Google Calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Detect scheduling conflicts for an appointment
     */
    public function detectConflicts(User $user, Carbon $startTime, Carbon $endTime, ?int $excludeAppointmentId = null): array
    {
        $conflicts = [];

        // Check Google Calendar busy times
        $busyTimes = $this->getUserBusyTimesInRange($user, $startTime, $endTime);

        foreach ($busyTimes as $busyTime) {
            if ($this->timesOverlap($startTime, $endTime, $busyTime['start'], $busyTime['end'])) {
                $conflicts[] = [
                    'type' => 'google_calendar',
                    'source' => 'Google Calendar',
                    'title' => $busyTime['summary'],
                    'start' => $busyTime['start']->toDateTimeString(),
                    'end' => $busyTime['end']->toDateTimeString(),
                ];
            }
        }

        // Check existing WhizIQ appointments
        $existingAppointments = \App\Models\Appointment::where('user_id', $user->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_datetime', [$startTime, $endTime])
                    ->orWhereBetween('end_datetime', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_datetime', '<=', $startTime)
                          ->where('end_datetime', '>=', $endTime);
                    });
            });

        if ($excludeAppointmentId) {
            $existingAppointments->where('id', '!=', $excludeAppointmentId);
        }

        foreach ($existingAppointments->get() as $existing) {
            $conflicts[] = [
                'type' => 'whiziq_appointment',
                'source' => 'WhizIQ Appointment',
                'title' => $existing->title,
                'start' => $existing->start_datetime->toDateTimeString(),
                'end' => $existing->end_datetime->toDateTimeString(),
                'appointment_id' => $existing->id,
            ];
        }

        // Check availability exceptions (vacation, time off)
        $exceptions = \App\Models\AvailabilityException::where('user_id', $user->id)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_date', [$startTime, $endTime])
                    ->orWhereBetween('end_date', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_date', '<=', $startTime)
                          ->where('end_date', '>=', $endTime);
                    });
            })
            ->get();

        foreach ($exceptions as $exception) {
            $conflicts[] = [
                'type' => 'availability_exception',
                'source' => 'Time Off / Exception',
                'title' => $exception->title . ' (' . ucfirst(str_replace('_', ' ', $exception->exception_type)) . ')',
                'start' => $exception->start_date->toDateTimeString(),
                'end' => $exception->end_date->toDateTimeString(),
                'is_all_day' => $exception->is_all_day,
            ];
        }

        return $conflicts;
    }

    /**
     * Build a detailed description for the appointment
     */
    protected function buildAppointmentDescription($appointment): string
    {
        $description = $appointment->description ?? '';

        $description .= "\n\n---\n";
        $description .= "WhizIQ Appointment Details:\n";

        if ($appointment->attendee_name) {
            $description .= "Attendee: {$appointment->attendee_name}\n";
        }

        if ($appointment->attendee_email) {
            $description .= "Email: {$appointment->attendee_email}\n";
        }

        if ($appointment->attendee_phone) {
            $description .= "Phone: {$appointment->attendee_phone}\n";
        }

        if ($appointment->attendee_company) {
            $description .= "Company: {$appointment->attendee_company}\n";
        }

        if ($appointment->location) {
            $description .= "Location: {$appointment->location}\n";
        }

        if ($appointment->meeting_url) {
            $description .= "Meeting URL: {$appointment->meeting_url}\n";
        }

        return trim($description);
    }
}
