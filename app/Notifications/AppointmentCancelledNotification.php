<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Appointment $appointment,
        public bool $forAttendee = false
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Attendees only get email, owners get both
        return $this->forAttendee ? ['mail'] : ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Appointment Cancelled')
            ->greeting('Hello' . ($this->forAttendee ? ' ' . $this->appointment->attendee_name : '') . '!')
            ->line('The following appointment has been cancelled:')
            ->line('')
            ->line('**' . $this->appointment->title . '**')
            ->line('Date & Time: ' . $this->appointment->start_datetime->format('F j, Y \a\t g:i A'))
            ->line('Duration: ' . $this->appointment->getDurationMinutesAttribute() . ' minutes');

        if ($this->appointment->cancellation_reason) {
            $mail->line('')
                ->line('**Cancellation Reason:**')
                ->line($this->appointment->cancellation_reason);
        }

        if ($this->forAttendee) {
            $mail->line('')
                ->line('We apologize for any inconvenience.')
                ->line('If you would like to reschedule, please visit our booking page.');
        } else {
            $mail->line('')
                ->line('Attendee: ' . $this->appointment->attendee_name)
                ->line('Email: ' . $this->appointment->attendee_email);
        }

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
            'attendee_name' => $this->appointment->attendee_name,
            'start_datetime' => $this->appointment->start_datetime->toDateTimeString(),
            'cancellation_reason' => $this->appointment->cancellation_reason,
        ];
    }
}
