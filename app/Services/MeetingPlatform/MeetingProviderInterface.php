<?php

namespace App\Services\MeetingPlatform;

use App\Models\Appointment;
use App\Models\BookingSetting;

interface MeetingProviderInterface
{
    /**
     * Create a meeting for an appointment
     *
     * @param Appointment $appointment
     * @param BookingSetting $settings
     * @return array ['url' => string, 'id' => string, 'password' => string|null]
     */
    public function createMeeting(Appointment $appointment, BookingSetting $settings): array;

    /**
     * Update an existing meeting
     *
     * @param Appointment $appointment
     * @param BookingSetting $settings
     * @return array ['url' => string, 'id' => string, 'password' => string|null]
     */
    public function updateMeeting(Appointment $appointment, BookingSetting $settings): array;

    /**
     * Delete a meeting
     *
     * @param Appointment $appointment
     * @param BookingSetting $settings
     * @return bool
     */
    public function deleteMeeting(Appointment $appointment, BookingSetting $settings): bool;

    /**
     * Check if the provider is configured
     *
     * @param BookingSetting $settings
     * @return bool
     */
    public function isConfigured(BookingSetting $settings): bool;
}
