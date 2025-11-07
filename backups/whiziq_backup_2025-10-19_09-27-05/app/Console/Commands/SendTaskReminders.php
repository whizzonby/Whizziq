<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tasks:send-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send reminders for tasks that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for task reminders...');

        // Find tasks with reminders that are due (within the next 5 minutes)
        $tasks = Task::where('reminder_enabled', true)
            ->whereNotNull('reminder_date')
            ->where('reminder_date', '<=', now()->addMinutes(5))
            ->where('reminder_date', '>', now()->subHour()) // Don't send old reminders
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDoesntHave('user', function ($query) {
                // Check if reminder was already sent recently (avoid duplicates)
                $query->whereHas('notifications', function ($q) {
                    $q->where('type', TaskReminderNotification::class)
                        ->where('created_at', '>', now()->subHour());
                });
            })
            ->with('user')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No reminders to send.');
            return 0;
        }

        $count = 0;

        foreach ($tasks as $task) {
            try {
                // Send notification to the task owner
                $task->user->notify(new TaskReminderNotification($task));

                $this->line("✓ Sent reminder for task: {$task->title} (User: {$task->user->name})");
                $count++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to send reminder for task: {$task->title} - " . $e->getMessage());
            }
        }

        $this->info("\nSent {$count} reminder(s) successfully.");
        return 0;
    }
}
