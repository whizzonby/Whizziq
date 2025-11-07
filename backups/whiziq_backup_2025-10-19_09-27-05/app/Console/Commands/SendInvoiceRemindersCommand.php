<?php

namespace App\Console\Commands;

use App\Services\InvoiceReminderService;
use Illuminate\Console\Command;

class SendInvoiceRemindersCommand extends Command
{
    protected $signature = 'invoices:send-reminders
                            {--overdue : Send only overdue notifications}
                            {--scheduled : Send only scheduled reminders}';

    protected $description = 'Send payment reminders for invoices';

    public function handle(InvoiceReminderService $reminderService): int
    {
        $this->info('Starting invoice reminder process...');

        $totalSent = 0;

        // Send overdue notifications
        if ($this->option('overdue') || !$this->option('scheduled')) {
            $this->info('Sending overdue notifications...');
            $overdueSent = $reminderService->sendOverdueNotifications();
            $totalSent += $overdueSent;
            $this->info("✓ Sent {$overdueSent} overdue notifications");
        }

        // Send scheduled reminders
        if ($this->option('scheduled') || !$this->option('overdue')) {
            $this->info('Sending scheduled reminders...');
            $scheduledSent = $reminderService->sendScheduledReminders();
            $totalSent += $scheduledSent;
            $this->info("✓ Sent {$scheduledSent} scheduled reminders");
        }

        $this->newLine();
        $this->info("✓ Total reminders sent: {$totalSent}");

        return self::SUCCESS;
    }
}
