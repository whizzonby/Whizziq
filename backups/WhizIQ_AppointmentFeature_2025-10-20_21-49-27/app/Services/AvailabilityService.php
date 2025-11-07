<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AvailabilitySchedule;
use App\Models\AppointmentType;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(int $userId, Carbon $date, int $durationMinutes = 30, int $minNoticeHours = 0): array
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

        // Remove booked slots
        $availableSlots = $this->filterBookedSlots($userId, $date, $slots, $durationMinutes);

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
    protected function filterBookedSlots(int $userId, Carbon $date, array $slots, int $duration): array
    {
        return array_filter($slots, function ($slot) use ($userId, $duration) {
            $slotStart = Carbon::parse($slot['datetime']);
            $slotEnd = $slotStart->copy()->addMinutes($duration);

            return !$this->isSlotBooked($userId, $slotStart, $slotEnd);
        });
    }

    /**
     * Check if a time slot is already booked
     */
    public function isSlotBooked(int $userId, Carbon $start, Carbon $end): bool
    {
        return Appointment::where('user_id', $userId)
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

        for ($i = 0; $i < $days; $i++) {
            $date = $current->copy()->addDays($i);
            $dayOfWeek = $date->dayOfWeek;

            // Check if user has availability on this day
            $hasAvailability = AvailabilitySchedule::forUser($userId)
                ->forDay($dayOfWeek)
                ->available()
                ->exists();

            if ($hasAvailability) {
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
