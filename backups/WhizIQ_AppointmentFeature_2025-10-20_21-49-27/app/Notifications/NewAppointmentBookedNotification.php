<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class NewAppointmentBookedNotification extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.dashboard.resources.appointments.edit', ['record' => $this->appointment]);

        $requiresApproval = $this->appointment->status === 'scheduled';

        return (new MailMessage)
            ->subject($requiresApproval ? 'New Appointment Request' : 'New Appointment Booked')
            ->greeting('Hello!')
            ->line($requiresApproval
                ? 'You have a new appointment request that requires your approval:'
                : 'You have a new appointment booking:')
            ->line('**' . $this->appointment->title . '**')
            ->line('Date & Time: ' . $this->appointment->start_datetime->format('F j, Y \a\t g:i A'))
            ->line('Duration: ' . $this->appointment->getDurationMinutesAttribute() . ' minutes')
            ->line('Attendee: ' . $this->appointment->attendee_name)
            ->line('Email: ' . $this->appointment->attendee_email)
            ->when($this->appointment->attendee_phone, fn ($mail) =>
                $mail->line('Phone: ' . $this->appointment->attendee_phone)
            )
            ->when($this->appointment->notes, fn ($mail) =>
                $mail->line('Notes: ' . $this->appointment->notes)
            )
            ->action($requiresApproval ? 'Review Request' : 'View Appointment', $url)
            ->line('Thank you for using our booking system!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'title' => $this->appointment->title,
            'attendee_name' => $this->appointment->attendee_name,
            'attendee_email' => $this->appointment->attendee_email,
            'start_datetime' => $this->appointment->start_datetime->toDateTimeString(),
            'status' => $this->appointment->status,
            'url' => route('filament.dashboard.resources.appointments.edit', ['record' => $this->appointment]),
        ];
    }

    /**
     * Send in-app Filament notification
     */
    public function toFilament(object $notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('New Appointment Booked')
            ->body($this->appointment->attendee_name . ' booked: ' . $this->appointment->title)
            ->icon('heroicon-o-calendar-days')
            ->iconColor('success')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Appointment')
                    ->url(route('filament.dashboard.resources.appointments.edit', ['record' => $this->appointment]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
