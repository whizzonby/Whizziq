<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:send-reminders {--hours=24 : Hours before appointment to send reminder}';

    /**
     * The console command description.
     */
    protected $description = 'Send reminders for upcoming appointments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursBeforeAppointment = (int) $this->option('hours');
        $this->info("Checking for appointment reminders ({$hoursBeforeAppointment} hours before)...");

        $targetTime = now()->addHours($hoursBeforeAppointment);

        // Find appointments that:
        // 1. Are confirmed or scheduled (not cancelled/completed)
        // 2. Start within the target timeframe
        // 3. Haven't been reminded yet (reminder_sent_at is null or old)
        $appointments = Appointment::whereIn('status', ['confirmed', 'scheduled'])
            ->whereBetween('start_datetime', [
                $targetTime->copy()->subMinutes(30), // 30-minute window
                $targetTime->copy()->addMinutes(30)
            ])
            ->where(function ($query) use ($hoursBeforeAppointment) {
                // Either no reminder sent, or last reminder was for a different timeframe
                $query->whereNull('reminder_sent_at')
                    ->orWhere('reminder_sent_at', '<', now()->subHours($hoursBeforeAppointment + 1));
            })
            ->with(['appointmentType', 'venue'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No reminders to send.');
            return 0;
        }

        $count = 0;

        foreach ($appointments as $appointment) {
            try {
                // Send reminder to attendee via email
                Notification::route('mail', $appointment->attendee_email)
                    ->notify(new AppointmentReminderNotification($appointment));

                // Mark reminder as sent
                $appointment->update(['reminder_sent_at' => now()]);

                $this->line(" Sent reminder for: {$appointment->title} to {$appointment->attendee_email}");
                $count++;
            } catch (\Exception $e) {
                $this->error(" Failed to send reminder for: {$appointment->title} - " . $e->getMessage());
            }
        }

        $this->info("Successfully sent {$count} appointment reminder(s).");
        return 0;
    }
}
