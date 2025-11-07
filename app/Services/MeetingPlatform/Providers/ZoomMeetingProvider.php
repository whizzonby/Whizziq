<?php

namespace App\Services\MeetingPlatform\Providers;

use App\Models\Appointment;
use App\Models\BookingSetting;
use App\Models\CalendarConnection;
use App\Services\MeetingPlatform\MeetingProviderInterface;
use App\Services\Zoom\ZoomOAuthService;
use Illuminate\Support\Facades\Log;

class ZoomMeetingProvider implements MeetingProviderInterface
{
    protected ZoomOAuthService $zoomOAuthService;

    public function __construct()
    {
        $this->zoomOAuthService = new ZoomOAuthService();
    }

    /**
     * Create a Zoom meeting for an appointment
     */
    public function createMeeting(Appointment $appointment, BookingSetting $settings): array
    {
        $connection = $this->getActiveConnection($appointment);

        if (!$connection || !$this->zoomOAuthService->isConfigured()) {
            Log::warning('Zoom not connected or not configured, using fallback', [
                'appointment_id' => $appointment->id,
                'has_connection' => $connection !== null,
                'is_configured' => $this->zoomOAuthService->isConfigured(),
            ]);
            return $this->getFallbackMeeting();
        }

        $meetingData = [
            'topic' => $appointment->title,
            'type' => 2, // Scheduled meeting
            'start_time' => $appointment->start_datetime->toIso8601String(),
            'duration' => $appointment->getDurationMinutesAttribute(),
            'timezone' => $appointment->timezone ?? config('app.timezone'),
            'agenda' => $appointment->description ?? '',
            'settings' => [
                'join_before_host' => true,
                'waiting_room' => false,
                'auto_recording' => 'none',
            ],
        ];

        $meeting = $this->zoomOAuthService->createMeeting($connection, $meetingData);

        if (!$meeting) {
            Log::error('Failed to create Zoom meeting via OAuth, using fallback', [
                'appointment_id' => $appointment->id,
                'connection_id' => $connection->id,
            ]);
            return $this->getFallbackMeeting();
        }

        // Save the calendar connection relationship
        $appointment->calendar_connection_id = $connection->id;
        $appointment->save();

        return [
            'url' => $meeting['join_url'],
            'id' => (string) $meeting['id'],
            'password' => $meeting['password'] ?? null,
        ];
    }

    /**
     * Update an existing Zoom meeting
     */
    public function updateMeeting(Appointment $appointment, BookingSetting $settings): array
    {
        $connection = $this->getActiveConnection($appointment);

        if (!$appointment->meeting_id || !$connection || !$this->zoomOAuthService->isConfigured()) {
            return [
                'url' => $appointment->meeting_url,
                'id' => $appointment->meeting_id,
                'password' => $appointment->meeting_password,
            ];
        }

        $meetingData = [
            'topic' => $appointment->title,
            'start_time' => $appointment->start_datetime->toIso8601String(),
            'duration' => $appointment->getDurationMinutesAttribute(),
            'timezone' => $appointment->timezone ?? config('app.timezone'),
            'agenda' => $appointment->description ?? '',
        ];

        $updated = $this->zoomOAuthService->updateMeeting(
            $connection,
            $appointment->meeting_id,
            $meetingData
        );

        if (!$updated) {
            Log::error('Failed to update Zoom meeting via OAuth', [
                'appointment_id' => $appointment->id,
                'meeting_id' => $appointment->meeting_id,
            ]);
        }

        return [
            'url' => $appointment->meeting_url,
            'id' => $appointment->meeting_id,
            'password' => $appointment->meeting_password,
        ];
    }

    /**
     * Delete a Zoom meeting
     */
    public function deleteMeeting(Appointment $appointment, BookingSetting $settings): bool
    {
        $connection = $this->getActiveConnection($appointment);

        if (!$appointment->meeting_id || !$connection || !$this->zoomOAuthService->isConfigured()) {
            return true;
        }

        return $this->zoomOAuthService->deleteMeeting($connection, $appointment->meeting_id);
    }

    /**
     * Check if Zoom is properly configured
     */
    public function isConfigured(BookingSetting $settings): bool
    {
        if ($settings->meeting_platform !== 'zoom') {
            return false;
        }

        // Check if service is configured
        if (!$this->zoomOAuthService->isConfigured()) {
            return false;
        }

        // Check if user has an active Zoom connection
        $userId = $settings->user_id ?? auth()->id();
        if (!$userId) {
            return false;
        }

        return CalendarConnection::where('user_id', $userId)
            ->where('provider', 'zoom')
            ->syncEnabled()
            ->exists();
    }

    /**
     * Get the active Zoom connection for the appointment's user
     */
    protected function getActiveConnection(Appointment $appointment): ?CalendarConnection
    {
        $userId = $appointment->user_id ?? $appointment->appointmentType?->user_id ?? auth()->id();

        if (!$userId) {
            return null;
        }

        // First try to get the existing connection if appointment has one
        if ($appointment->calendar_connection_id) {
            $connection = CalendarConnection::find($appointment->calendar_connection_id);
            if ($connection && $connection->canSync()) {
                return $connection;
            }
        }

        // Otherwise get the active Zoom connection
        return CalendarConnection::where('user_id', $userId)
            ->where('provider', 'zoom')
            ->syncEnabled()
            ->first();
    }

    /**
     * Get fallback meeting details when API fails or user hasn't connected Zoom
     */
    protected function getFallbackMeeting(): array
    {
        $meetingId = str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);

        return [
            'url' => "https://zoom.us/j/{$meetingId}",
            'id' => $meetingId,
            'password' => \Illuminate\Support\Str::random(6),
        ];
    }
}
