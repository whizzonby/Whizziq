<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\FollowUpReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SmartNotificationService
{
    /**
     * Get all pending notifications for a user
     */
    public function getNotifications(int $userId): Collection
    {
        $notifications = collect();

        // Overdue follow-ups
        $notifications = $notifications->merge($this->getOverdueFollowUps($userId));

        // Contacts not contacted recently
        $notifications = $notifications->merge($this->getStaleContacts($userId));

        // Deals closing soon
        $notifications = $notifications->merge($this->getDealsClosingSoon($userId));

        // Overdue deals
        $notifications = $notifications->merge($this->getOverdueDeals($userId));

        // Deals stuck in stage
        $notifications = $notifications->merge($this->getStuckDeals($userId));

        return $notifications->sortByDesc('priority')->values();
    }

    /**
     * Overdue follow-up reminders
     */
    protected function getOverdueFollowUps(int $userId): Collection
    {
        $overdue = FollowUpReminder::where('user_id', $userId)
            ->where('status', 'pending')
            ->where('remind_at', '<', now())
            ->with(['contact', 'deal'])
            ->get();

        return $overdue->map(function($reminder) {
            return [
                'id' => 'followup_' . $reminder->id,
                'type' => 'overdue_followup',
                'priority' => 'high',
                'title' => 'Overdue Follow-Up',
                'message' => "Follow up with {$reminder->contact->name} is overdue",
                'action_text' => 'View Contact',
                'action_url' => route('filament.dashboard.resources.contacts.edit', ['record' => $reminder->contact_id]),
                'created_at' => $reminder->remind_at,
                'icon' => 'heroicon-o-bell-alert',
                'color' => 'danger',
            ];
        });
    }

    /**
     * Contacts not contacted in 30+ days
     */
    protected function getStaleContacts(int $userId): Collection
    {
        $stale = Contact::where('user_id', $userId)
            ->where('status', 'active')
            ->where('last_contact_date', '<', now()->subDays(30))
            ->where('type', '!=', 'archived')
            ->orderBy('last_contact_date', 'asc')
            ->limit(5)
            ->get();

        return $stale->map(function($contact) {
            $daysSince = now()->diffInDays($contact->last_contact_date);

            return [
                'id' => 'stale_' . $contact->id,
                'type' => 'stale_contact',
                'priority' => $contact->priority === 'vip' ? 'high' : 'medium',
                'title' => 'Stale Contact',
                'message' => "No contact with {$contact->name} in {$daysSince} days",
                'action_text' => 'Reach Out',
                'action_url' => route('filament.dashboard.resources.contacts.edit', ['record' => $contact->id]),
                'created_at' => $contact->last_contact_date,
                'icon' => 'heroicon-o-user-circle',
                'color' => 'warning',
            ];
        });
    }

    /**
     * Deals closing in next 7 days
     */
    protected function getDealsClosingSoon(int $userId): Collection
    {
        $closingSoon = Deal::where('user_id', $userId)
            ->open()
            ->whereBetween('expected_close_date', [now(), now()->addDays(7)])
            ->with('contact')
            ->get();

        return $closingSoon->map(function($deal) {
            $daysUntil = now()->diffInDays($deal->expected_close_date);

            return [
                'id' => 'closing_' . $deal->id,
                'type' => 'deal_closing_soon',
                'priority' => $daysUntil <= 2 ? 'high' : 'medium',
                'title' => 'Deal Closing Soon',
                'message' => "{$deal->title} expected to close in {$daysUntil} day(s)",
                'action_text' => 'View Deal',
                'action_url' => route('filament.dashboard.resources.deals.edit', ['record' => $deal->id]),
                'created_at' => now(),
                'icon' => 'heroicon-o-calendar',
                'color' => 'info',
            ];
        });
    }

    /**
     * Deals past expected close date
     */
    protected function getOverdueDeals(int $userId): Collection
    {
        $overdue = Deal::where('user_id', $userId)
            ->open()
            ->where('expected_close_date', '<', now())
            ->with('contact')
            ->orderBy('expected_close_date', 'asc')
            ->get();

        return $overdue->map(function($deal) {
            $daysOverdue = $deal->expected_close_date->diffInDays(now());

            return [
                'id' => 'overdue_' . $deal->id,
                'type' => 'deal_overdue',
                'priority' => 'high',
                'title' => 'Overdue Deal',
                'message' => "{$deal->title} is {$daysOverdue} day(s) overdue",
                'action_text' => 'Update Deal',
                'action_url' => route('filament.dashboard.resources.deals.edit', ['record' => $deal->id]),
                'created_at' => $deal->expected_close_date,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
            ];
        });
    }

    /**
     * Deals stuck in same stage for 30+ days
     */
    protected function getStuckDeals(int $userId): Collection
    {
        $stuck = Deal::where('user_id', $userId)
            ->open()
            ->where('updated_at', '<', now()->subDays(30))
            ->with('contact')
            ->get();

        return $stuck->map(function($deal) {
            $daysStuck = now()->diffInDays($deal->updated_at);

            return [
                'id' => 'stuck_' . $deal->id,
                'type' => 'deal_stuck',
                'priority' => 'medium',
                'title' => 'Stuck Deal',
                'message' => "{$deal->title} in {$deal->stage_label} for {$daysStuck} days",
                'action_text' => 'Review Deal',
                'action_url' => route('filament.dashboard.resources.deals.edit', ['record' => $deal->id]),
                'created_at' => $deal->updated_at,
                'icon' => 'heroicon-o-pause-circle',
                'color' => 'warning',
            ];
        });
    }

    /**
     * Get notification count
     */
    public function getCount(int $userId): int
    {
        return $this->getNotifications($userId)->count();
    }

    /**
     * Get high priority notifications only
     */
    public function getHighPriority(int $userId): Collection
    {
        return $this->getNotifications($userId)
            ->where('priority', 'high')
            ->values();
    }
}
