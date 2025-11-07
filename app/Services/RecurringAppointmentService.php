<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringAppointmentService
{
    /**
     * Create recurring appointment instances based on the parent appointment
     */
    public function createRecurringInstances(Appointment $parentAppointment): array
    {
        if (!$parentAppointment->is_recurring) {
            return [];
        }

        $instances = [];
        $occurrences = $this->generateOccurrences($parentAppointment);

        foreach ($occurrences as $occurrence) {
            try {
                $instance = Appointment::create([
                    'user_id' => $parentAppointment->user_id,
                    'contact_id' => $parentAppointment->contact_id,
                    'appointment_type_id' => $parentAppointment->appointment_type_id,
                    'title' => $parentAppointment->title,
                    'description' => $parentAppointment->description,
                    'location' => $parentAppointment->location,
                    'start_datetime' => $occurrence['start'],
                    'end_datetime' => $occurrence['end'],
                    'timezone' => $parentAppointment->timezone,
                    'status' => $parentAppointment->status,
                    'attendee_name' => $parentAppointment->attendee_name,
                    'attendee_email' => $parentAppointment->attendee_email,
                    'attendee_phone' => $parentAppointment->attendee_phone,
                    'attendee_company' => $parentAppointment->attendee_company,
                    'notes' => $parentAppointment->notes,
                    'booked_via' => $parentAppointment->booked_via,
                    'meeting_platform' => $parentAppointment->meeting_platform,
                    'recurring_parent_id' => $parentAppointment->id,
                    'is_recurring' => false,
                ]);

                $instances[] = $instance;

                Log::info('Created recurring appointment instance', [
                    'parent_id' => $parentAppointment->id,
                    'instance_id' => $instance->id,
                    'start_date' => $occurrence['start'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create recurring instance', [
                    'parent_id' => $parentAppointment->id,
                    'occurrence' => $occurrence,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $instances;
    }

    /**
     * Generate occurrence dates based on recurrence rules
     */
    protected function generateOccurrences(Appointment $appointment): array
    {
        $occurrences = [];
        $current = $appointment->start_datetime->copy();
        $duration = $appointment->start_datetime->diffInMinutes($appointment->end_datetime);

        $maxOccurrences = $appointment->recurrence_count ?? 52; // Default max 1 year of weekly
        $endDate = $appointment->recurrence_end_date
            ? Carbon::parse($appointment->recurrence_end_date)->endOfDay()
            : $current->copy()->addYear();

        $count = 0;

        while ($count < $maxOccurrences && $current->lte($endDate)) {
            // Skip the first occurrence (it's the parent)
            if ($count > 0) {
                $occurrences[] = [
                    'start' => $current->copy(),
                    'end' => $current->copy()->addMinutes($duration),
                ];
            }

            // Move to next occurrence based on recurrence type
            $current = $this->getNextOccurrence($current, $appointment);

            if (!$current || $current->gt($endDate)) {
                break;
            }

            $count++;
        }

        return $occurrences;
    }

    /**
     * Calculate the next occurrence date based on recurrence rules
     */
    protected function getNextOccurrence(Carbon $current, Appointment $appointment): ?Carbon
    {
        $interval = $appointment->recurrence_interval ?? 1;

        switch ($appointment->recurrence_type) {
            case 'daily':
                return $current->copy()->addDays($interval);

            case 'weekly':
                if ($appointment->recurrence_days && count($appointment->recurrence_days) > 0) {
                    return $this->getNextWeeklyOccurrence($current, $appointment->recurrence_days, $interval);
                }
                return $current->copy()->addWeeks($interval);

            case 'monthly':
                return $current->copy()->addMonths($interval);

            default:
                return null;
        }
    }

    /**
     * Get next occurrence for weekly recurrence with specific days
     */
    protected function getNextWeeklyOccurrence(Carbon $current, array $days, int $interval): Carbon
    {
        $next = $current->copy()->addDay();
        $weeksAdded = 0;
        $daysChecked = 0;
        $maxDays = 60; // Safety limit

        while ($daysChecked < $maxDays) {
            $dayOfWeek = $next->dayOfWeek;

            if (in_array($dayOfWeek, $days)) {
                // Check if we've completed enough weeks
                if ($weeksAdded >= $interval - 1) {
                    return $next;
                }
            }

            // If we're at Sunday (0), increment weeks counter
            if ($dayOfWeek == 0 && $daysChecked > 0) {
                $weeksAdded++;
            }

            $next->addDay();
            $daysChecked++;
        }

        return $next;
    }

    /**
     * Update all recurring instances when parent is updated
     */
    public function updateRecurringInstances(Appointment $parentAppointment, array $fieldsToUpdate): int
    {
        if (!$parentAppointment->isRecurringParent()) {
            return 0;
        }

        $count = 0;
        $instances = $parentAppointment->recurringInstances;

        foreach ($instances as $instance) {
            // Only update future instances
            if ($instance->start_datetime->isFuture()) {
                $updateData = [];

                // Update specific fields that should cascade
                $cascadeFields = ['title', 'description', 'location', 'attendee_name', 'attendee_email', 'attendee_phone', 'attendee_company'];

                foreach ($cascadeFields as $field) {
                    if (isset($fieldsToUpdate[$field])) {
                        $updateData[$field] = $fieldsToUpdate[$field];
                    }
                }

                if (!empty($updateData)) {
                    $instance->update($updateData);
                    $count++;
                }
            }
        }

        Log::info('Updated recurring instances', [
            'parent_id' => $parentAppointment->id,
            'updated_count' => $count,
        ]);

        return $count;
    }

    /**
     * Cancel all future recurring instances
     */
    public function cancelFutureInstances(Appointment $parentAppointment, ?string $reason = null): int
    {
        if (!$parentAppointment->isRecurringParent()) {
            return 0;
        }

        $count = 0;
        $instances = $parentAppointment->recurringInstances()
            ->where('start_datetime', '>', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->get();

        foreach ($instances as $instance) {
            $instance->cancel($reason);
            $count++;
        }

        Log::info('Cancelled future recurring instances', [
            'parent_id' => $parentAppointment->id,
            'cancelled_count' => $count,
        ]);

        return $count;
    }

    /**
     * Delete all recurring instances
     */
    public function deleteAllInstances(Appointment $parentAppointment): int
    {
        if (!$parentAppointment->isRecurringParent()) {
            return 0;
        }

        $count = $parentAppointment->recurringInstances()->count();
        $parentAppointment->recurringInstances()->delete();

        Log::info('Deleted all recurring instances', [
            'parent_id' => $parentAppointment->id,
            'deleted_count' => $count,
        ]);

        return $count;
    }

    /**
     * Get summary of recurring appointment series
     */
    public function getRecurringSummary(Appointment $parentAppointment): array
    {
        if (!$parentAppointment->isRecurringParent()) {
            return [];
        }

        $instances = $parentAppointment->recurringInstances;

        return [
            'total_instances' => $instances->count(),
            'upcoming' => $instances->where('start_datetime', '>', now())->count(),
            'completed' => $instances->where('status', 'completed')->count(),
            'cancelled' => $instances->where('status', 'cancelled')->count(),
            'first_occurrence' => $parentAppointment->start_datetime->format('M d, Y'),
            'last_occurrence' => $instances->max('start_datetime')?->format('M d, Y'),
            'recurrence_description' => $parentAppointment->recurrence_description,
        ];
    }
}
