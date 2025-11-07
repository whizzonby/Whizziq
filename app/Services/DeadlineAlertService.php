<?php

namespace App\Services;

use App\Models\User;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class DeadlineAlertService
{
    /**
     * Send deadline alerts to users
     */
    public function sendDeadlineAlerts(): void
    {
        $users = User::whereHas('taxSetting', function($query) {
            $query->where('reminder_enabled', true);
        })->get();
        
        foreach ($users as $user) {
            $this->checkAndSendAlerts($user);
        }
    }

    /**
     * Check and send alerts for a specific user
     */
    public function checkAndSendAlerts(User $user): void
    {
        $taxSetting = $user->taxSetting;
        
        if (!$taxSetting || !$taxSetting->reminder_enabled) {
            return;
        }
        
        $reminderDays = $taxSetting->reminder_days_before ?? 7;
        $deadlines = $this->getUpcomingDeadlines($user);
        
        foreach ($deadlines as $deadline) {
            if ($this->shouldSendAlert($deadline, $reminderDays)) {
                $this->sendAlert($user, $deadline);
            }
        }
    }

    /**
     * Get upcoming deadlines for user
     */
    public function getUpcomingDeadlines(User $user): array
    {
        $taxSetting = $user->taxSetting;
        $currentYear = now()->year;
        
        $deadlines = [];
        
        // Annual filing deadline
        $annualDeadline = $this->getAnnualFilingDeadline($currentYear);
        $deadlines[] = [
            'type' => 'annual_filing',
            'title' => 'Annual Tax Filing',
            'due_date' => $annualDeadline,
            'days_remaining' => now()->diffInDays($annualDeadline, false),
            'priority' => $this->getPriority($annualDeadline),
            'description' => 'File your annual tax return',
        ];
        
        // Quarterly estimated tax deadlines
        $quarterlyDeadlines = $this->getQuarterlyDeadlines($currentYear);
        foreach ($quarterlyDeadlines as $deadline) {
            $deadlines[] = [
                'type' => 'quarterly_estimated',
                'title' => "Q{$deadline['quarter']} Estimated Tax Payment",
                'due_date' => $deadline['due_date'],
                'days_remaining' => now()->diffInDays($deadline['due_date'], false),
                'priority' => $this->getPriority($deadline['due_date']),
                'description' => "Quarterly estimated tax payment for Q{$deadline['quarter']}",
            ];
        }
        
        // Business-specific deadlines
        if ($taxSetting && $taxSetting->business_type === 'corporation') {
            $corpDeadline = $this->getCorporationDeadline($currentYear);
            $deadlines[] = [
                'type' => 'corporation_filing',
                'title' => 'Corporation Tax Return',
                'due_date' => $corpDeadline,
                'days_remaining' => now()->diffInDays($corpDeadline, false),
                'priority' => $this->getPriority($corpDeadline),
                'description' => 'File your corporation tax return',
            ];
        }
        
        return $deadlines;
    }

    /**
     * Get annual filing deadline
     */
    protected function getAnnualFilingDeadline(int $year): Carbon
    {
        // Individual returns due April 15th
        $deadline = Carbon::create($year + 1, 4, 15);
        
        // If April 15th falls on weekend, move to next business day
        if ($deadline->isWeekend()) {
            $deadline->nextWeekday();
        }
        
        return $deadline;
    }

    /**
     * Get quarterly deadlines
     */
    protected function getQuarterlyDeadlines(int $year): array
    {
        return [
            [
                'quarter' => 1,
                'due_date' => Carbon::create($year, 4, 15),
            ],
            [
                'quarter' => 2,
                'due_date' => Carbon::create($year, 6, 15),
            ],
            [
                'quarter' => 3,
                'due_date' => Carbon::create($year, 9, 15),
            ],
            [
                'quarter' => 4,
                'due_date' => Carbon::create($year + 1, 1, 15),
            ],
        ];
    }

    /**
     * Get corporation deadline
     */
    protected function getCorporationDeadline(int $year): Carbon
    {
        // C-Corp returns due March 15th
        return Carbon::create($year + 1, 3, 15);
    }

    /**
     * Check if should send alert
     */
    protected function shouldSendAlert(array $deadline, int $reminderDays): bool
    {
        $daysRemaining = $deadline['days_remaining'];
        
        // Send alert if within reminder period
        if ($daysRemaining <= $reminderDays && $daysRemaining >= 0) {
            return true;
        }
        
        // Send urgent alert if overdue
        if ($daysRemaining < 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Send alert to user
     */
    protected function sendAlert(User $user, array $deadline): void
    {
        $alertData = [
            'user' => $user,
            'deadline' => $deadline,
            'urgency' => $this->getUrgencyLevel($deadline),
        ];
        
        // Send email notification
        $this->sendEmailAlert($user, $deadline);
        
        // Send in-app notification
        $this->sendInAppNotification($user, $deadline);
        
        // Log the alert
        \Log::info("Tax deadline alert sent to user {$user->id}: {$deadline['title']}");
    }

    /**
     * Send email alert
     */
    protected function sendEmailAlert(User $user, array $deadline): void
    {
        $subject = $this->getEmailSubject($deadline);
        $message = $this->getEmailMessage($user, $deadline);
        
        // This would integrate with your email service
        Mail::raw($message, function($mail) use ($user, $subject) {
            $mail->to($user->email)
                 ->subject($subject);
        });
    }

    /**
     * Send in-app notification
     */
    protected function sendInAppNotification(User $user, array $deadline): void
    {
        // This would integrate with your notification system
        // For now, we'll just log it
        \Log::info("In-app notification: {$deadline['title']} due in {$deadline['days_remaining']} days");
    }

    /**
     * Get priority level
     */
    protected function getPriority(Carbon $deadline): string
    {
        $daysRemaining = now()->diffInDays($deadline, false);
        
        if ($daysRemaining < 0) {
            return 'overdue';
        } elseif ($daysRemaining <= 3) {
            return 'urgent';
        } elseif ($daysRemaining <= 7) {
            return 'high';
        } elseif ($daysRemaining <= 14) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Get urgency level
     */
    protected function getUrgencyLevel(array $deadline): string
    {
        $daysRemaining = $deadline['days_remaining'];
        
        if ($daysRemaining < 0) {
            return 'overdue';
        } elseif ($daysRemaining <= 1) {
            return 'critical';
        } elseif ($daysRemaining <= 3) {
            return 'urgent';
        } elseif ($daysRemaining <= 7) {
            return 'high';
        }
        
        return 'normal';
    }

    /**
     * Get email subject
     */
    protected function getEmailSubject(array $deadline): string
    {
        $daysRemaining = $deadline['days_remaining'];
        
        if ($daysRemaining < 0) {
            return "🚨 OVERDUE: {$deadline['title']}";
        } elseif ($daysRemaining <= 3) {
            return "⚠️ URGENT: {$deadline['title']} Due Soon";
        } elseif ($daysRemaining <= 7) {
            return "📅 Reminder: {$deadline['title']} Due Soon";
        }
        
        return "📋 Tax Deadline: {$deadline['title']}";
    }

    /**
     * Get email message
     */
    protected function getEmailMessage(User $user, array $deadline): string
    {
        $daysRemaining = $deadline['days_remaining'];
        $urgency = $this->getUrgencyLevel($deadline);
        
        $message = "Hello {$user->name},\n\n";
        
        if ($daysRemaining < 0) {
            $message .= "🚨 URGENT: Your tax deadline has passed!\n\n";
            $message .= "The deadline for {$deadline['title']} was {$deadline['due_date']->format('F j, Y')}.\n";
            $message .= "Please file immediately to avoid penalties.\n\n";
        } elseif ($daysRemaining <= 3) {
            $message .= "⚠️ URGENT: Your tax deadline is approaching!\n\n";
            $message .= "{$deadline['title']} is due in {$daysRemaining} days ({$deadline['due_date']->format('F j, Y')}).\n\n";
        } else {
            $message .= "📅 Reminder: Tax deadline approaching\n\n";
            $message .= "{$deadline['title']} is due in {$daysRemaining} days ({$deadline['due_date']->format('F j, Y')}).\n\n";
        }
        
        $message .= "Description: {$deadline['description']}\n\n";
        
        if ($urgency === 'overdue' || $urgency === 'critical') {
            $message .= "🚨 ACTION REQUIRED: Please log into your WhizIQ dashboard and file your taxes immediately.\n\n";
        } else {
            $message .= "💡 TIP: Log into your WhizIQ dashboard to review and file your taxes.\n\n";
        }
        
        $message .= "Best regards,\n";
        $message .= "WhizIQ Tax Team";
        
        return $message;
    }

    /**
     * Get dashboard deadline summary
     */
    public function getDashboardDeadlines(User $user): array
    {
        $deadlines = $this->getUpcomingDeadlines($user);
        
        $urgent = collect($deadlines)->where('priority', 'urgent')->count();
        $overdue = collect($deadlines)->where('priority', 'overdue')->count();
        $upcoming = collect($deadlines)->where('days_remaining', '>', 0)->count();
        
        return [
            'total_deadlines' => count($deadlines),
            'urgent_deadlines' => $urgent,
            'overdue_deadlines' => $overdue,
            'upcoming_deadlines' => $upcoming,
            'next_deadline' => collect($deadlines)->where('days_remaining', '>', 0)->sortBy('days_remaining')->first(),
            'deadlines' => $deadlines,
        ];
    }
}
