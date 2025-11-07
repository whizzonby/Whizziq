<?php

namespace App\Notifications;

use App\Models\TaxPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaxDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TaxPeriod $taxPeriod,
        public int $daysUntilDeadline
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysUntilDeadline <= 3 ? 'URGENT' : 'Important';
        $subject = "{$urgency}: Tax Filing Deadline Approaching - {$this->taxPeriod->name}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that your tax filing deadline is approaching:")
            ->line("**Tax Period:** {$this->taxPeriod->name}")
            ->line("**Filing Deadline:** {$this->taxPeriod->filing_deadline->format('F d, Y')}")
            ->line("**Days Remaining:** {$this->daysUntilDeadline} days")
            ->line("**Period:** {$this->taxPeriod->start_date->format('M d, Y')} - {$this->taxPeriod->end_date->format('M d, Y')}")
            ->action('View Tax Dashboard', url('/dashboard/tax-dashboard-page'))
            ->line('Make sure to complete and file your tax return before the deadline to avoid penalties.')
            ->line('Need help? Review your tax summary and compliance status in your dashboard.')
            ->salutation("Best regards,\n" . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tax_period_id' => $this->taxPeriod->id,
            'tax_period_name' => $this->taxPeriod->name,
            'filing_deadline' => $this->taxPeriod->filing_deadline->toDateString(),
            'days_until_deadline' => $this->daysUntilDeadline,
            'message' => "Tax filing deadline for {$this->taxPeriod->name} is in {$this->daysUntilDeadline} days",
        ];
    }
}
