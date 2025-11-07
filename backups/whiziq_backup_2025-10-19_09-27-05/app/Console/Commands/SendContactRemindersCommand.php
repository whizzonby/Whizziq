<?php

namespace App\Console\Commands;

use App\Services\ContactReminderService;
use Illuminate\Console\Command;

class SendContactRemindersCommand extends Command
{
    protected $signature = 'contacts:send-reminders
                            {--today : Send only today\'s reminders}
                            {--due : Send only overdue reminders}';

    protected $description = 'Send follow-up reminders for contacts';

    public function handle(ContactReminderService $reminderService): int
    {
        $this->info('Starting contact reminder process...');

        $totalSent = 0;

        if ($this->option('today')) {
            // Send only today's reminders
            $this->info('Sending today\'s reminders...');
            $sent = $reminderService->sendTodayReminders();
            $totalSent += $sent;
            $this->info("✓ Sent {$sent} reminders for today");
        } elseif ($this->option('due')) {
            // Send only overdue reminders
            $this->info('Sending overdue reminders...');
            $sent = $reminderService->sendDueReminders();
            $totalSent += $sent;
            $this->info("✓ Sent {$sent} overdue reminders");
        } else {
            // Send all due reminders (default)
            $this->info('Sending all due reminders...');
            $sent = $reminderService->sendDueReminders();
            $totalSent += $sent;
            $this->info("✓ Sent {$sent} due reminders");
        }

        $this->newLine();
        $this->info("✓ Total reminders sent: {$totalSent}");

        return self::SUCCESS;
    }
}
