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

        return (new MailMessage)
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
            ->when($this->appointment->appointmentType->price > 0, fn ($mail) =>
                $mail->line('Price: $' . number_format($this->appointment->appointmentType->price, 2))
            )
            ->when($this->appointment->location, fn ($mail) =>
                $mail->line('Location: ' . $this->appointment->location)
            )
            ->line('')
            ->when(!$requiresApproval, fn ($mail) =>
                $mail->line('We look forward to meeting with you!')
            )
            ->when($requiresApproval, fn ($mail) =>
                $mail->line('You will receive a confirmation email once your request is approved.')
            )
            ->line('If you need to make any changes, please contact us directly.')
            ->salutation('Regards, ' . $senderName);
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
