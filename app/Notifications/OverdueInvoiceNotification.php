<?php

namespace App\Notifications;

use App\Models\ClientInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientInvoice $invoice
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice #{$this->invoice->invoice_number} is Now Overdue")
            ->greeting("Hello {$notifiable->name},")
            ->line("Invoice #{$this->invoice->invoice_number} for {$this->invoice->client->name} is now overdue.")
            ->line("**Amount Due:** \${$this->invoice->balance_due}")
            ->line("**Due Date:** {$this->invoice->due_date->format('M d, Y')}")
            ->line("**Days Overdue:** {$this->invoice->days_overdue} days")
            ->action('View Invoice', route('filament.dashboard.resources.client-invoices.edit', $this->invoice))
            ->line('Consider sending a payment reminder to your client.');
    }

    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'client_name' => $this->invoice->client->name,
            'amount_due' => $this->invoice->balance_due,
            'days_overdue' => $this->invoice->days_overdue,
            'message' => "Invoice #{$this->invoice->invoice_number} for {$this->invoice->client->name} is overdue by {$this->invoice->days_overdue} days. Amount: \${$this->invoice->balance_due}",
        ];
    }
}
