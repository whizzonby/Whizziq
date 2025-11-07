<?php

namespace App\Console\Commands;

use App\Models\TaxPeriod;
use App\Notifications\TaxDeadlineReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaxDeadlineRemindersCommand extends Command
{
    protected $signature = 'tax:send-reminders';

    protected $description = 'Send email reminders for upcoming tax filing deadlines';

    public function handle()
    {
        $this->info('Checking for upcoming tax deadlines...');

        // Get periods with deadlines in the next 7 days
        $upcomingDeadlines = TaxPeriod::where('status', '!=', 'filed')
            ->where('filing_deadline', '>=', now())
            ->where('filing_deadline', '<=', now()->addDays(7))
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($upcomingDeadlines as $period) {
            $user = $period->user;

            // Check if user has reminders enabled
            if ($user->taxSetting && !$user->taxSetting->reminder_enabled) {
                continue;
            }

            $daysUntil = now()->diffInDays($period->filing_deadline);

            // Send reminder
            try {
                $user->notify(new TaxDeadlineReminderNotification($period, $daysUntil));
                $sent++;

                $this->info("✓ Sent reminder to {$user->email} for {$period->name} ({$daysUntil} days)");
            } catch (\Exception $e) {
                $this->error("✗ Failed to send reminder to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("✓ Sent {$sent} tax deadline reminders.");

        return Command::SUCCESS;
    }
}
