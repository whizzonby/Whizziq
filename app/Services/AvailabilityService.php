<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AvailabilitySchedule;
use App\Models\AppointmentType;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    protected CalendarSyncService $calendarSyncService;

    public function __construct()
    {
        $this->calendarSyncService = new CalendarSyncService();
    }
    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(int $userId, Carbon $date, int $durationMinutes = 30, int $minNoticeHours = 0, ?int $venueId = null): array
    {
        // Get user's availability for this day of week
        $dayOfWeek = $date->dayOfWeek;

        $schedule = AvailabilitySchedule::forUser($userId)
            ->forDay($dayOfWeek)
            ->available()
            ->first();

        if (!$schedule) {
            return []; // Not available on this day
        }

        // Generate time slots
        $slots = $this->generateTimeSlots($date, $schedule, $durationMinutes, $minNoticeHours);

        // Remove booked slots (including venue availability check)
        $availableSlots = $this->filterBookedSlots($userId, $date, $slots, $durationMinutes, $venueId);

        return $availableSlots;
    }

    /**
     * Generate time slots based on availability schedule
     */
    protected function generateTimeSlots(Carbon $date, AvailabilitySchedule $schedule, int $duration, int $minNoticeHours = 0): array
    {
        $slots = [];

        // Parse start and end times
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);

        // Create datetime objects for the specific date
        $current = $date->copy()->setTimeFrom($startTime);
        $end = $date->copy()->setTimeFrom($endTime);

        // Calculate minimum booking time
        $minBookingTime = now()->addHours($minNoticeHours);

        // Generate slots
        while ($current->copy()->addMinutes($duration)->lte($end)) {
            // Skip slots that don't meet minimum notice requirement
            if ($current->isAfter($minBookingTime)) {
                $slots[] = [
                    'time' => $current->format('H:i'),
                    'datetime' => $current->toDateTimeString(),
                    'formatted' => $current->format('g:i A'),
                ];
            }

            $current->addMinutes($duration);
        }

        return $slots;
    }

    /**
     * Filter out already booked slots
     */
    protected function filterBookedSlots(int $userId, Carbon $date, array $slots, int $duration, ?int $venueId = null): array
    {
        if (empty($slots)) {
            return [];
        }

        // PERFORMANCE FIX: Fetch all appointments for the day at once
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $appointments = Appointment::where('user_id', $userId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($dayStart, $dayEnd) {
                $query->whereBetween('start_datetime', [$dayStart, $dayEnd])
                    ->orWhereBetween('end_datetime', [$dayStart, $dayEnd])
                    ->orWhere(function ($q) use ($dayStart, $dayEnd) {
                        $q->where('start_datetime', '<=', $dayStart)
                          ->where('end_datetime', '>=', $dayEnd);
                    });
            })
            ->get(['start_datetime', 'end_datetime', 'venue_id']);

        // Get external calendar busy times once for the whole day
        $user = User::find($userId);
        $externalBusyTimes = [];
        if ($user) {
            $externalBusyTimes = $this->calendarSyncService->getBusyTimesForDay($user, $date);
        }

        // Filter slots in memory
        return array_values(array_filter($slots, function ($slot) use ($userId, $duration, $venueId, $appointments, $externalBusyTimes) {
            $slotStart = Carbon::parse($slot['datetime']);
            $slotEnd = $slotStart->copy()->addMinutes($duration);

            // Check local appointments
            foreach ($appointments as $appointment) {
                $appointmentStart = Carbon::parse($appointment->start_datetime);
                $appointmentEnd = Carbon::parse($appointment->end_datetime);

                // Check for overlap
                if ($slotStart->lt($appointmentEnd) && $slotEnd->gt($appointmentStart)) {
                    // If venue is specified, only conflict if same venue
                    if ($venueId && $appointment->venue_id && $appointment->venue_id != $venueId) {
                        continue;
                    }
                    return false;
                }
            }

            // Check external calendar busy times
            foreach ($externalBusyTimes as $busyTime) {
                if ($slotStart->lt($busyTime['end']) && $slotEnd->gt($busyTime['start'])) {
                    return false;
                }
            }

            // If venue is specified, check venue availability
            if ($venueId) {
                $venueAvailable = $this->isVenueAvailableFromCache($venueId, $slotStart, $slotEnd, $appointments);
                if (!$venueAvailable) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Check venue availability using cached appointments
     */
    protected function isVenueAvailableFromCache(int $venueId, Carbon $start, Carbon $end, $appointments): bool
    {
        foreach ($appointments as $appointment) {
            if ($appointment->venue_id != $venueId) {
                continue;
            }

            $appointmentStart = Carbon::parse($appointment->start_datetime);
            $appointmentEnd = Carbon::parse($appointment->end_datetime);

            // Check for overlap
            if ($start->lt($appointmentEnd) && $end->gt($appointmentStart)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a time slot is already booked
     */
    public function isSlotBooked(int $userId, Carbon $start, Carbon $end, ?int $venueId = null): bool
    {
        // Check local appointments
        $hasLocalAppointment = Appointment::where('user_id', $userId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($start, $end) {
                // Check for overlapping appointments
                $query->whereBetween('start_datetime', [$start, $end])
                    ->orWhereBetween('end_datetime', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_datetime', '<=', $start)
                          ->where('end_datetime', '>=', $end);
                    });
            })
            ->exists();

        if ($hasLocalAppointment) {
            return true;
        }

        // If venue is specified, check venue availability
        if ($venueId && !$this->isVenueAvailable($venueId, $start, $end)) {
            return true;
        }

        // Check external calendar busy times
        $user = User::find($userId);
        if ($user) {
            $isAvailableOnExternalCalendar = $this->calendarSyncService->isTimeSlotAvailable($user, $start, $end);
            if (!$isAvailableOnExternalCalendar) {
                return true; // Slot is busy on external calendar
            }
        }

        return false;
    }

    /**
     * Check if a slot is available (inverse of isSlotBooked)
     */
    public function isSlotAvailable(int $userId, Carbon $start, Carbon $end): bool
    {
        return !$this->isSlotBooked($userId, $start, $end);
    }

    /**
     * Get available dates for the next N days
     */
    public function getAvailableDates(int $userId, int $days = 30): array
    {
        $availableDates = [];
        $current = now()->startOfDay();

        // PERFORMANCE FIX: Fetch all schedules at once instead of 30 separate queries
        $schedules = AvailabilitySchedule::forUser($userId)
            ->available()
            ->get()
            ->keyBy('day_of_week');

        for ($i = 0; $i < $days; $i++) {
            $date = $current->copy()->addDays($i);
            $dayOfWeek = $date->dayOfWeek;

            // Check if user has availability on this day (from in-memory collection)
            if ($schedules->has($dayOfWeek)) {
                $availableDates[] = [
                    'date' => $date->toDateString(),
                    'formatted' => $date->format('M d, Y'),
                    'day_name' => $date->format('l'),
                ];
            }
        }

        return $availableDates;
    }

    /**
     * Check if a venue is available for a specific time slot
     */
    public function isVenueAvailable(int $venueId, Carbon $start, Carbon $end): bool
    {
        $venue = Venue::find($venueId);

        if (!$venue || !$venue->is_active) {
            return false; // Venue doesn't exist or is inactive
        }

        // Check if venue is already booked during this time
        $hasBooking = Appointment::where('venue_id', $venueId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($start, $end) {
                // Check for overlapping appointments
                $query->whereBetween('start_datetime', [$start, $end])
                    ->orWhereBetween('end_datetime', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_datetime', '<=', $start)
                          ->where('end_datetime', '>=', $end);
                    });
            })
            ->exists();

        if ($hasBooking) {
            return false;
        }

        // Check venue capacity
        if ($venue->capacity) {
            $concurrentBookings = Appointment::where('venue_id', $venueId)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->where(function ($query) use ($start, $end) {
                    // Check for any overlapping appointments
                    $query->whereBetween('start_datetime', [$start, $end])
                        ->orWhereBetween('end_datetime', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('start_datetime', '<=', $start)
                              ->where('end_datetime', '>=', $end);
                        })
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('start_datetime', '>=', $start)
                              ->where('start_datetime', '<', $end);
                        })
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('end_datetime', '>', $start)
                              ->where('end_datetime', '<=', $end);
                        });
                })
                ->count();

            if ($concurrentBookings >= $venue->capacity) {
                return false; // Venue is at capacity
            }
        }

        return true;
    }

    /**
     * Get available venues for a specific time slot (PERFORMANCE OPTIMIZED)
     */
    public function getAvailableVenues(int $userId, Carbon $start, Carbon $end): Collection
    {
        // PERFORMANCE FIX: Get all active venues first
        $venues = Venue::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        if ($venues->isEmpty()) {
            return collect([]);
        }

        // Get all appointments that could conflict with this time slot in one query
        $appointments = Appointment::whereIn('venue_id', $venues->pluck('id'))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_datetime', [$start, $end])
                    ->orWhereBetween('end_datetime', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_datetime', '<=', $start)
                          ->where('end_datetime', '>=', $end);
                    });
            })
            ->get(['id', 'venue_id', 'start_datetime', 'end_datetime']);

        // Group appointments by venue for quick lookup
        $appointmentsByVenue = $appointments->groupBy('venue_id');

        // Filter venues based on cached appointment data
        return $venues->filter(function ($venue) use ($start, $end, $appointmentsByVenue) {
            $venueAppointments = $appointmentsByVenue->get($venue->id, collect([]));

            // Check for overlaps
            foreach ($venueAppointments as $appointment) {
                $appointmentStart = Carbon::parse($appointment->start_datetime);
                $appointmentEnd = Carbon::parse($appointment->end_datetime);

                if ($start->lt($appointmentEnd) && $end->gt($appointmentStart)) {
                    return false; // Venue is booked
                }
            }

            // Check venue capacity if set
            if ($venue->capacity) {
                $concurrentBookings = $venueAppointments->count();
                if ($concurrentBookings >= $venue->capacity) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * Get available slots for a specific date and venue (if specified)
     */
    public function getAvailableSlotsForVenue(int $userId, Carbon $date, int $durationMinutes, ?int $venueId = null, int $minNoticeHours = 0): array
    {
        $slots = $this->getAvailableSlots($userId, $date, $durationMinutes, $minNoticeHours);

        // If venue is specified, filter by venue availability
        if ($venueId) {
            $slots = array_filter($slots, function ($slot) use ($userId, $durationMinutes, $venueId) {
                $slotStart = Carbon::parse($slot['datetime']);
                $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

                return $this->isVenueAvailable($venueId, $slotStart, $slotEnd);
            });
        }

        return array_values($slots);
    }

    /**
     * Create default availability schedule (Mon-Fri, 9 AM - 5 PM)
     */
    public function createDefaultSchedule(int $userId): void
    {
        // Monday to Friday, 9 AM - 5 PM
        for ($day = 1; $day <= 5; $day++) {
            AvailabilitySchedule::create([
                'user_id' => $userId,
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_available' => true,
            ]);
        }

        // Weekend - not available
        for ($day = 0; $day <= 6; $day += 6) {
            AvailabilitySchedule::create([
                'user_id' => $userId,
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_available' => false,
            ]);
        }
    }
}
