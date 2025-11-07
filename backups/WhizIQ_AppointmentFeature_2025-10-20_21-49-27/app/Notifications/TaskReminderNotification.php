<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task
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
        $url = route('filament.dashboard.resources.tasks.view', ['record' => $this->task]);

        return (new MailMessage)
            ->subject('Task Reminder: ' . $this->task->title)
            ->greeting('Hello!')
            ->line('This is a reminder about your task:')
            ->line('**' . $this->task->title . '**')
            ->when($this->task->description, fn ($mail) =>
                $mail->line($this->task->description)
            )
            ->when($this->task->due_date, fn ($mail) =>
                $mail->line('Due: ' . $this->task->due_date->format('F j, Y'))
            )
            ->line('Priority: ' . ucfirst($this->task->priority))
            ->action('View Task', $url)
            ->line('Stay productive!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_priority' => $this->task->priority,
            'due_date' => $this->task->due_date?->toDateString(),
            'url' => route('filament.dashboard.resources.tasks.view', ['record' => $this->task]),
        ];
    }

    /**
     * Send in-app Filament notification
     */
    public function toFilament(object $notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Task Reminder')
            ->body($this->task->title)
            ->icon('heroicon-o-bell')
            ->iconColor($this->task->priority_color)
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Task')
                    ->url(route('filament.dashboard.resources.tasks.view', ['record' => $this->task]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
