<?php

namespace App\Notifications;

use App\Models\ClientInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientInvoice $invoice,
        public string $reminderType = 'standard' // standard, first, second, final
    ) {}

    public function via($notifiable): array
    {
        // This notification is primarily for sending to clients via email
        // We can also log it in the database for the user
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage;

        $subject = match($this->reminderType) {
            'first' => "Reminder: Invoice #{$this->invoice->invoice_number} Due Soon",
            'second' => "Second Reminder: Invoice #{$this->invoice->invoice_number} Payment Due",
            'final' => "Final Reminder: Invoice #{$this->invoice->invoice_number} Payment Overdue",
            default => "Payment Reminder: Invoice #{$this->invoice->invoice_number}",
        };

        $greeting = "Dear {$this->invoice->client->name},";

        $intro = match($this->reminderType) {
            'first' => "This is a friendly reminder that invoice #{$this->invoice->invoice_number} is due soon.",
            'second' => "This is a second reminder regarding invoice #{$this->invoice->invoice_number}.",
            'final' => "This is a final reminder that invoice #{$this->invoice->invoice_number} is now overdue.",
            default => "This is a payment reminder for invoice #{$this->invoice->invoice_number}.",
        };

        $message
            ->subject($subject)
            ->greeting($greeting)
            ->line($intro)
            ->line('')
            ->line("**Invoice Number:** {$this->invoice->invoice_number}")
            ->line("**Invoice Date:** {$this->invoice->invoice_date->format('M d, Y')}")
            ->line("**Due Date:** {$this->invoice->due_date->format('M d, Y')}")
            ->line("**Amount Due:** \${$this->invoice->balance_due}");

        if ($this->invoice->is_overdue) {
            $message->line("**Days Overdue:** {$this->invoice->days_overdue} days")
                ->line('⚠️ This invoice is overdue. Please remit payment at your earliest convenience.');
        }

        if ($this->invoice->terms) {
            $message->line('')
                ->line('**Payment Terms:**')
                ->line($this->invoice->terms);
        }

        $message->line('')
            ->line('If you have any questions regarding this invoice, please contact us.')
            ->line('Thank you for your business!');

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'client_name' => $this->invoice->client->name,
            'amount_due' => $this->invoice->balance_due,
            'reminder_type' => $this->reminderType,
        ];
    }
}
