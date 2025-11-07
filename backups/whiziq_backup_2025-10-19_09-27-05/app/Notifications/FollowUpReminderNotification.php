<?php

namespace App\Notifications;

use App\Models\FollowUpReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowUpReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FollowUpReminder $reminder
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $contact = $this->reminder->contact;

        return (new MailMessage)
            ->subject("Follow-Up Reminder: {$this->reminder->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**Follow-Up Reminder:** {$this->reminder->title}")
            ->line('')
            ->line("**Contact:** {$contact->name}" . ($contact->company ? " - {$contact->company}" : ''))
            ->line("**Email:** " . ($contact->email ?? 'N/A'))
            ->line("**Phone:** " . ($contact->phone ?? 'N/A'))
            ->when($this->reminder->description, function ($message) {
                return $message->line('')
                    ->line("**Details:**")
                    ->line($this->reminder->description);
            })
            ->when($this->reminder->deal, function ($message) {
                return $message->line('')
                    ->line("**Related Deal:** {$this->reminder->deal->title} (\${$this->reminder->deal->value})");
            })
            ->action('View Contact', route('filament.dashboard.resources.contacts.edit', $contact))
            ->line('Don\'t let this relationship go cold!');
    }

    public function toArray($notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'contact_id' => $this->reminder->contact_id,
            'contact_name' => $this->reminder->contact->name,
            'title' => $this->reminder->title,
            'description' => $this->reminder->description,
            'priority' => $this->reminder->priority,
            'message' => "Follow-up reminder: {$this->reminder->title} for {$this->reminder->contact->name}",
        ];
    }
}
