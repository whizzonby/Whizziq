<?php

namespace App\Services;

use App\Models\ClientInvoice;
use App\Models\User;
use App\Notifications\OverdueInvoiceNotification;
use App\Notifications\PaymentReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InvoiceReminderService
{
    /**
     * Send overdue invoice notifications to users
     */
    public function sendOverdueNotifications(): int
    {
        $sent = 0;

        // Get all invoices that just became overdue today
        $overdueInvoices = ClientInvoice::query()
            ->with(['client', 'user'])
            ->where('status', 'overdue')
            ->whereDate('due_date', '<', now())
            ->where(function ($query) {
                // Either never sent a notification, or last notification was more than 1 day ago
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<', now()->subDay());
            })
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                $invoice->user->notify(new OverdueInvoiceNotification($invoice));
                $sent++;

                Log::info("Overdue notification sent for invoice #{$invoice->invoice_number}");
            } catch (\Exception $e) {
                Log::error("Failed to send overdue notification for invoice #{$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Send payment reminders based on due dates
     */
    public function sendScheduledReminders(): int
    {
        $sent = 0;

        // First reminder: 3 days before due date
        $sent += $this->sendRemindersForDueDate(now()->addDays(3), 'first');

        // Second reminder: 1 day after due date
        $sent += $this->sendRemindersForDueDate(now()->subDay(), 'second');

        // Final reminder: 7 days after due date
        $sent += $this->sendRemindersForDueDate(now()->subDays(7), 'final');

        return $sent;
    }

    /**
     * Send reminders for invoices due on a specific date
     */
    protected function sendRemindersForDueDate(Carbon $dueDate, string $reminderType): int
    {
        $sent = 0;

        $invoices = ClientInvoice::query()
            ->with(['client', 'user'])
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereDate('due_date', $dueDate->toDateString())
            ->where(function ($query) {
                // Don't send if already sent a reminder in the last 24 hours
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<', now()->subHours(24));
            })
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->canSendReminder()) {
                try {
                    $this->sendReminderToClient($invoice, $reminderType);
                    $invoice->recordReminderSent();
                    $sent++;

                    Log::info("Payment reminder ({$reminderType}) sent for invoice #{$invoice->invoice_number}");
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder for invoice #{$invoice->invoice_number}: {$e->getMessage()}");
                }
            }
        }

        return $sent;
    }

    /**
     * Send a reminder to a specific client
     */
    public function sendReminderToClient(ClientInvoice $invoice, string $reminderType = 'standard'): bool
    {
        try {
            // Send email to client
            if ($invoice->client->email) {
                // Create a notifiable for the client (since they might not be a User)
                $clientNotifiable = new class($invoice->client->email, $invoice->client->name) {
                    public function __construct(
                        public string $email,
                        public string $name
                    ) {}

                    public function routeNotificationForMail(): string
                    {
                        return $this->email;
                    }
                };

                $clientNotifiable->notify(new PaymentReminderNotification($invoice, $reminderType));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to send reminder to client for invoice #{$invoice->invoice_number}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send manual reminder for a specific invoice
     */
    public function sendManualReminder(ClientInvoice $invoice): bool
    {
        if (!$invoice->canSendReminder()) {
            return false;
        }

        $reminderType = match(true) {
            $invoice->days_overdue > 7 => 'final',
            $invoice->days_overdue > 0 => 'second',
            default => 'first',
        };

        $result = $this->sendReminderToClient($invoice, $reminderType);

        if ($result) {
            $invoice->recordReminderSent();
        }

        return $result;
    }

    /**
     * Get invoices that need reminders
     */
    public function getInvoicesNeedingReminders(): Collection
    {
        return ClientInvoice::query()
            ->with(['client', 'user'])
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->where(function ($query) {
                $query->where('due_date', '<=', now()->addDays(3))
                    ->orWhere('status', 'overdue');
            })
            ->where(function ($query) {
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<', now()->subHours(24));
            })
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
