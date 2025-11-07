<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmedNotification extends Notification implements ShouldQueue
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
        // Attendees don't have database notifications, only email
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $requiresApproval = $this->appointment->status === 'scheduled';

        // Get booking settings to use display name
        $bookingSetting = $this->appointment->user->bookingSetting ?? null;
        $senderName = $bookingSetting?->display_name ?? $this->appointment->user->name;

        $mail = (new MailMessage)
            ->subject($requiresApproval ? 'Appointment Request Received' : 'Appointment Confirmed')
            ->greeting('Hello ' . $this->appointment->attendee_name . '!')
            ->line($requiresApproval
                ? 'Thank you for your appointment request. We will review it and get back to you shortly.'
                : 'Your appointment has been confirmed!')
            ->line('')
            ->line('**Appointment Details:**')
            ->line('Service: ' . $this->appointment->appointmentType->name)
            ->line('Date & Time: ' . $this->appointment->start_datetime->format('l, F j, Y \a\t g:i A'))
            ->line('Duration: ' . $this->appointment->getDurationMinutesAttribute() . ' minutes')
            ->line('Timezone: ' . $this->appointment->timezone)
            ->when($this->appointment->appointmentType->price > 0, fn ($m) =>
                $m->line('Price: $' . number_format($this->appointment->appointmentType->price, 2))
            )
            ->when($this->appointment->location, fn ($m) =>
                $m->line('Location: ' . $this->appointment->location)
            );

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

            if ($venue->access_instructions) {
                $mail->line('')
                    ->line('**Access Instructions:**')
                    ->line($venue->access_instructions);
            }

            if ($venue->directions) {
                $mail->line('')
                    ->line('**Directions:**')
                    ->line($venue->directions);
            }

            if ($venue->phone) {
                $mail->line('')
                    ->line('Venue Phone: ' . $venue->phone);
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

        // Add calendar download link
        if ($this->appointment->confirmation_token) {
            $calendarUrl = route('appointment.calendar.download', ['token' => $this->appointment->confirmation_token]);
            $mail->line('')
                ->line('**Add to Your Calendar:**')
                ->action('Add to Calendar', $calendarUrl);
        }

        $mail->line('')
            ->when(!$requiresApproval, fn ($m) =>
                $m->line('We look forward to meeting with you!')
            )
            ->when($requiresApproval, fn ($m) =>
                $m->line('You will receive a confirmation email once your request is approved.')
            )
            ->line('If you need to make any changes, please contact us directly.')
            ->salutation('Regards, ' . $senderName);

        return $mail;
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
            'status' => $this->appointment->status,
        ];
    }
}
