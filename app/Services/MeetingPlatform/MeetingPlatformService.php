<?php

namespace App\Services\MeetingPlatform;

use App\Models\Appointment;
use App\Models\BookingSetting;
use App\Services\MeetingPlatform\Providers\ZoomMeetingProvider;
use App\Services\MeetingPlatform\Providers\GoogleMeetProvider;

class MeetingPlatformService
{
    protected array $providers = [];

    public function __construct()
    {
        $this->providers = [
            'zoom' => new ZoomMeetingProvider(),
            'google_meet' => new GoogleMeetProvider(),
        ];
    }

    /**
     * Create a meeting for an appointment based on booking settings
     *
     * @param Appointment $appointment
     * @param BookingSetting $settings
     * @return Appointment
     */
    public function createMeetingForAppointment(Appointment $appointment, BookingSetting $settings): Appointment
    {
        // Check if meeting platform is enabled
        if ($settings->meeting_platform === 'none' || !$settings->meeting_platform) {
            return $appointment;
        }

        $provider = $this->getProvider($settings->meeting_platform);

        if (!$provider || !$provider->isConfigured($settings)) {
            return $appointment;
        }

        try {
            $meetingDetails = $provider->createMeeting($appointment, $settings);

            $appointment->update([
                'meeting_platform' => $settings->meeting_platform,
                'meeting_url' => $meetingDetails['url'],
                'meeting_id' => $meetingDetails['id'],
                'meeting_password' => $meetingDetails['password'],
            ]);

            return $appointment->fresh();
        } catch (\Exception $e) {
            // Log error but don't fail the booking
            \Log::error('Failed to create meeting', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
                'platform' => $settings->meeting_platform,
            ]);

            return $appointment;
        }
    }

    /**
     * Update a meeting for an appointment
     *
     * @param Appointment $appointment
     * @param BookingSetting $settings
     * @return Appointment
     */
    public function updateMeetingForAppointment(Appointment $appointment, BookingSetting $settings): Appointment
    {
        if (!$appointment->meeting_platform || !$appointment->meeting_id) {
            return $appointment;
        }

        $provider = $this->getProvider($appointment->meeting_platform);

        if (!$provider) {
            return $appointment;
        }

        try {
            $meetingDetails = $provider->updateMeeting($appointment, $settings);

            $appointment->update([
                'meeting_url' => $meetingDetails['url'],
                'meeting_id' => $meetingDetails['id'],
                'meeting_password' => $meetingDetails['password'],
            ]);

            return $appointment->fresh();
        } catch (\Exception $e) {
            \Log::error('Failed to update meeting', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
                'platform' => $appointment->meeting_platform,
            ]);

            return $appointment;
        }
    }

    /**
     * Delete a meeting for an appointment
     *
     * @param Appointment $appointment
     * @param BookingSetting $settings
     * @return bool
     */
    public function deleteMeetingForAppointment(Appointment $appointment, BookingSetting $settings): bool
    {
        if (!$appointment->meeting_platform || !$appointment->meeting_id) {
            return true;
        }

        $provider = $this->getProvider($appointment->meeting_platform);

        if (!$provider) {
            return false;
        }

        try {
            return $provider->deleteMeeting($appointment, $settings);
        } catch (\Exception $e) {
            \Log::error('Failed to delete meeting', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
                'platform' => $appointment->meeting_platform,
            ]);

            return false;
        }
    }

    /**
     * Get the provider for a specific platform
     *
     * @param string $platform
     * @return MeetingProviderInterface|null
     */
    protected function getProvider(string $platform): ?MeetingProviderInterface
    {
        return $this->providers[$platform] ?? null;
    }

    /**
     * Check if a platform is configured
     *
     * @param BookingSetting $settings
     * @return bool
     */
    public function isPlatformConfigured(BookingSetting $settings): bool
    {
        if ($settings->meeting_platform === 'none' || !$settings->meeting_platform) {
            return false;
        }

        $provider = $this->getProvider($settings->meeting_platform);

        return $provider && $provider->isConfigured($settings);
    }
}
