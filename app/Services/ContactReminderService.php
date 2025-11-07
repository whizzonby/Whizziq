<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\FollowUpReminder;
use App\Models\User;
use App\Notifications\FollowUpReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ContactReminderService
{
    /**
     * Send reminders for all due follow-ups
     */
    public function sendDueReminders(): int
    {
        $sent = 0;

        $reminders = FollowUpReminder::with(['user', 'contact', 'deal'])
            ->due()
            ->get();

        foreach ($reminders as $reminder) {
            try {
                $reminder->user->notify(new FollowUpReminderNotification($reminder));
                $reminder->markAsSent();
                $sent++;

                Log::info("Follow-up reminder sent for contact #{$reminder->contact_id}: {$reminder->title}");
            } catch (\Exception $e) {
                Log::error("Failed to send follow-up reminder #{$reminder->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Send reminders for today's follow-ups
     */
    public function sendTodayReminders(): int
    {
        $sent = 0;

        $reminders = FollowUpReminder::with(['user', 'contact', 'deal'])
            ->dueToday()
            ->get();

        foreach ($reminders as $reminder) {
            try {
                $reminder->user->notify(new FollowUpReminderNotification($reminder));
                $reminder->markAsSent();
                $sent++;

                Log::info("Today's follow-up reminder sent for contact #{$reminder->contact_id}: {$reminder->title}");
            } catch (\Exception $e) {
                Log::error("Failed to send today's reminder #{$reminder->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Send digest of upcoming reminders (next 7 days)
     */
    public function sendUpcomingDigest(User $user, int $days = 7): bool
    {
        $reminders = FollowUpReminder::with(['contact', 'deal'])
            ->forUser($user->id)
            ->upcoming($days)
            ->orderBy('remind_at')
            ->get();

        if ($reminders->isEmpty()) {
            return false;
        }

        // TODO: Create a digest notification
        // For now, just log it
        Log::info("User #{$user->id} has {$reminders->count()} upcoming follow-ups in the next {$days} days");

        return true;
    }

    /**
     * Alert on cold contacts (no contact in 30+ days)
     */
    public function alertColdContacts(User $user, int $daysThreshold = 30): Collection
    {
        $coldContacts = Contact::forUser($user->id)
            ->active()
            ->where(function ($query) use ($daysThreshold) {
                $query->where('last_contact_date', '<', now()->subDays($daysThreshold))
                    ->orWhereNull('last_contact_date');
            })
            ->whereIn('type', ['client', 'partner', 'investor']) // Important relationships
            ->orderBy('last_contact_date', 'asc')
            ->limit(10)
            ->get();

        // Auto-update relationship strength
        foreach ($coldContacts as $contact) {
            $contact->updateRelationshipStrength();
        }

        return $coldContacts;
    }

    /**
     * Get overdue follow-ups
     */
    public function getOverdueFollowUps(?int $userId = null): Collection
    {
        $query = FollowUpReminder::with(['contact', 'deal'])
            ->overdue();

        if ($userId) {
            $query->forUser($userId);
        }

        return $query->orderBy('remind_at', 'asc')->get();
    }

    /**
     * Get contacts needing attention
     */
    public function getContactsNeedingAttention(User $user): array
    {
        return [
            'overdue_follow_ups' => Contact::forUser($user->id)
                ->needsFollowUp()
                ->with('reminders')
                ->limit(10)
                ->get(),

            'cold_contacts' => $this->alertColdContacts($user, 30),

            'vip_no_recent_contact' => Contact::forUser($user->id)
                ->vip()
                ->where(function ($query) {
                    $query->where('last_contact_date', '<', now()->subDays(14))
                        ->orWhereNull('last_contact_date');
                })
                ->get(),

            'high_value_deals_stalled' => $user->deals()
                ->open()
                ->where('value', '>', 10000)
                ->where('updated_at', '<', now()->subDays(7))
                ->with('contact')
                ->get(),
        ];
    }

    /**
     * Create automatic follow-up reminders based on interaction outcome
     */
    public function createAutoReminder(Contact $contact, string $outcome, ?int $dealId = null): ?FollowUpReminder
    {
        // Define auto-reminder rules based on outcome
        $reminderConfig = match($outcome) {
            'follow_up_needed' => [
                'days' => 3,
                'title' => 'Follow up from recent interaction',
                'priority' => 'high',
            ],
            'positive' => [
                'days' => 7,
                'title' => 'Check in after positive interaction',
                'priority' => 'medium',
            ],
            'neutral' => [
                'days' => 14,
                'title' => 'Follow up on discussion',
                'priority' => 'medium',
            ],
            'negative' => [
                'days' => 30,
                'title' => 'Re-engage after negative interaction',
                'priority' => 'low',
            ],
            default => null,
        };

        if (!$reminderConfig) {
            return null;
        }

        return $contact->scheduleFollowUp(
            date: now()->addDays($reminderConfig['days']),
            title: $reminderConfig['title'],
            description: "Automatically scheduled based on interaction outcome",
            priority: $reminderConfig['priority'],
            dealId: $dealId
        );
    }

    /**
     * Bulk reschedule reminders
     */
    public function rescheduleReminders(Collection $reminderIds, \Carbon\Carbon $newDate): int
    {
        $updated = 0;

        foreach ($reminderIds as $id) {
            $reminder = FollowUpReminder::find($id);
            if ($reminder && $reminder->status === 'pending') {
                $reminder->remind_at = $newDate;
                $reminder->save();
                $updated++;
            }
        }

        return $updated;
    }
}
