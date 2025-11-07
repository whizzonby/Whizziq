<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Appointment $appointment
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Send to attendee via email only
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $timeUntil = now()->diffInHours($this->appointment->start_datetime);
        $timeText = $timeUntil < 24
            ? $timeUntil . ' hours'
            : ceil($timeUntil / 24) . ' days';

        return (new MailMessage)
            ->subject('Appointment Reminder: ' . $this->appointment->title)
            ->greeting('Hello ' . $this->appointment->attendee_name . '!')
            ->line('This is a friendly reminder about your upcoming appointment.')
            ->line('')
            ->line('**Appointment Details:**')
            ->line('Service: ' . $this->appointment->appointmentType->name)
            ->line('Date & Time: ' . $this->appointment->start_datetime->format('l, F j, Y \a\t g:i A'))
            ->line('Duration: ' . $this->appointment->getDurationMinutesAttribute() . ' minutes')
            ->line('Timezone: ' . $this->appointment->timezone);

        // Add venue details for in-person appointments
        if ($this->appointment->venue) {
            $venue = $this->appointment->venue;
            $mail->line('')
                ->line('**Location Details:**')
                ->line('Venue: ' . $venue->name);

            if ($venue->full_address) {
                $mail->line('Address: ' . $venue->full_address);
            }

            if ($venue->google_maps_url) {
                $mail->action('View on Google Maps', $venue->google_maps_url);
            }

            if ($this->appointment->room_name) {
                $mail->line('Room/Area: ' . $this->appointment->room_name);
            }

            if ($venue->parking_info) {
                $mail->line('')
                    ->line('**Parking:**')
                    ->line($venue->parking_info);
            }

            if ($venue->directions) {
                $mail->line('')
                    ->line('**Directions:**')
                    ->line($venue->directions);
            }
        } elseif ($this->appointment->location) {
            // Fallback to legacy location field
            $mail->line('')
                ->line('**Location:**')
                ->line($this->appointment->location);
        }

        // Add meeting link if available (for online or hybrid)
        if ($this->appointment->meeting_url) {
            $mail->line('')
                ->line('**Join Meeting:**')
                ->line('Platform: ' . ucfirst(str_replace('_', ' ', $this->appointment->meeting_platform)))
                ->action('Join Meeting', $this->appointment->meeting_url);

            if ($this->appointment->meeting_id) {
                $mail->line('Meeting ID: ' . $this->appointment->meeting_id);
            }

            if ($this->appointment->meeting_password) {
                $mail->line('Password: ' . $this->appointment->meeting_password);
            }
        }

        $mail->line('')
            ->line('Your appointment is in **' . $timeText . '**.')
            ->line('We look forward to seeing you!')
            ->line('')
            ->line('If you need to make any changes, please contact us as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'title' => $this->appointment->title,
            'start_datetime' => $this->appointment->start_datetime->toDateTimeString(),
        ];
    }
}
