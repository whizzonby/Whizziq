<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

class CalendarFileService
{
    /**
     * Generate an ICS calendar file for an appointment
     *
     * @param Appointment $appointment
     * @return string
     */
    public function generateICS(Appointment $appointment): string
    {
        $startTime = $this->formatDateTime($appointment->start_datetime);
        $endTime = $this->formatDateTime($appointment->end_datetime);
        $now = $this->formatDateTime(now());

        // Create a unique ID for the event
        $uid = 'appointment-' . $appointment->id . '@' . config('app.url');

        // Build the description with meeting details
        $description = $this->buildDescription($appointment);

        // Organizer information
        $organizerName = $appointment->user->name ?? 'Organizer';
        $organizerEmail = $appointment->user->email ?? 'noreply@example.com';

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//WhizzIQ//Appointment Booking//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "DTSTART:{$startTime}\r\n";
        $ics .= "DTEND:{$endTime}\r\n";
        $ics .= "DTSTAMP:{$now}\r\n";
        $ics .= "ORGANIZER;CN={$organizerName}:mailto:{$organizerEmail}\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "SUMMARY:{$this->escapeString($appointment->title)}\r\n";

        // Set location from venue or legacy field
        $location = '';
        if ($appointment->venue) {
            $venue = $appointment->venue;
            $location = $venue->name;
            if ($venue->full_address) {
                $location .= ', ' . $venue->full_address;
            }
            if ($appointment->room_name) {
                $location .= ' (' . $appointment->room_name . ')';
            }
        } elseif ($appointment->location) {
            $location = $appointment->location;
        }

        if ($location) {
            $ics .= "LOCATION:{$this->escapeString($location)}\r\n";
        }

        $ics .= "DESCRIPTION:{$this->escapeString($description)}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "SEQUENCE:0\r\n";

        // Add meeting URL if available
        if ($appointment->meeting_url) {
            $ics .= "URL:{$appointment->meeting_url}\r\n";
        }

        // Add alarm (reminder) 30 minutes before
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT30M\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:Reminder: {$this->escapeString($appointment->title)}\r\n";
        $ics .= "END:VALARM\r\n";

        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Build the description with meeting details
     *
     * @param Appointment $appointment
     * @return string
     */
    protected function buildDescription(Appointment $appointment): string
    {
        $description = '';

        if ($appointment->description) {
            $description .= $appointment->description . "\\n\\n";
        }

        // Add venue details for in-person appointments
        if ($appointment->venue) {
            $venue = $appointment->venue;
            $description .= "Location: " . $venue->name . "\\n";
            
            if ($venue->full_address) {
                $description .= "Address: " . $venue->full_address . "\\n";
            }
            
            if ($appointment->room_name) {
                $description .= "Room/Area: " . $appointment->room_name . "\\n";
            }
            
            if ($venue->google_maps_url) {
                $description .= "Map: " . $venue->google_maps_url . "\\n";
            }
            
            if ($venue->parking_info) {
                $description .= "\\nParking: " . $venue->parking_info . "\\n";
            }
            
            if ($venue->directions) {
                $description .= "Directions: " . $venue->directions . "\\n";
            }
            
            $description .= "\\n";
        } elseif ($appointment->location) {
            // Fallback to legacy location field
            $description .= "Location: " . $appointment->location . "\\n\\n";
        }

        // Add meeting details for online appointments
        if ($appointment->meeting_url) {
            $description .= "Join Meeting: " . $appointment->meeting_url . "\\n";

            if ($appointment->meeting_id) {
                $description .= "Meeting ID: " . $appointment->meeting_id . "\\n";
            }

            if ($appointment->meeting_password) {
                $description .= "Password: " . $appointment->meeting_password . "\\n";
            }

            $description .= "\\n";
        }

        if ($appointment->attendee_email) {
            $description .= "Attendee: " . $appointment->attendee_name . " (" . $appointment->attendee_email . ")\\n";
        }

        if ($appointment->notes) {
            $description .= "\\nNotes: " . $appointment->notes;
        }

        return $description;
    }

    /**
     * Format a datetime to ICS format (UTC)
     *
     * @param Carbon $dateTime
     * @return string
     */
    protected function formatDateTime(Carbon $dateTime): string
    {
        // Convert to UTC and format as: 20250120T150000Z
        return $dateTime->clone()->utc()->format('Ymd\THis\Z');
    }

    /**
     * Escape special characters for ICS format
     *
     * @param string $string
     * @return string
     */
    protected function escapeString(string $string): string
    {
        // ICS requires escaping of special characters
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(',', '\\,', $string);
        $string = str_replace(';', '\\;', $string);
        $string = str_replace("\n", '\\n', $string);
        $string = str_replace("\r", '', $string);

        return $string;
    }

    /**
     * Get the filename for the ICS file
     *
     * @param Appointment $appointment
     * @return string
     */
    public function getFilename(Appointment $appointment): string
    {
        $slug = \Str::slug($appointment->title);
        $date = $appointment->start_datetime->format('Y-m-d');

        return "appointment-{$slug}-{$date}.ics";
    }
}
