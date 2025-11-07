<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\ContactsOverviewWidget;
use App\Filament\Dashboard\Widgets\DealPipelineWidget;
use App\Filament\Dashboard\Widgets\FollowUpsDueWidget;
use App\Filament\Dashboard\Widgets\OutstandingInvoicesWidget;
use App\Filament\Dashboard\Widgets\OverdueInvoicesAlertWidget;
use App\Filament\Dashboard\Widgets\RecentInteractionsWidget;
use App\Filament\Dashboard\Widgets\RecentPaymentsWidget;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\ContactInteraction;
use App\Models\FollowUpReminder;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use BackedEnum;

class CRMDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'filament.dashboard.pages.crm-dashboard';

    protected static ?string $navigationLabel = 'CRM Dashboard';

    protected static UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'CRM Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            ContactsOverviewWidget::class,
            DealPipelineWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            OutstandingInvoicesWidget::class,
            RecentPaymentsWidget::class,
            RecentInteractionsWidget::class,
            FollowUpsDueWidget::class,
            OverdueInvoicesAlertWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
        ];
    }


    public function getStats(): array
    {
        $userId = auth()->id();

        // Contact Stats
        $totalContacts = Contact::where('user_id', $userId)->count();
        $activeContacts = Contact::where('user_id', $userId)->where('status', 'active')->count();
        $vipContacts = Contact::where('user_id', $userId)->where('priority', 'vip')->count();
        $contactsNeedingFollowUp = Contact::where('user_id', $userId)->needsFollowUp()->count();

        // Deal Stats
        $openDeals = Deal::where('user_id', $userId)->open()->get();
        $wonDeals = Deal::where('user_id', $userId)->won()->get();
        $lostDeals = Deal::where('user_id', $userId)->lost()->get();
        $closedDeals = Deal::where('user_id', $userId)->closed()->get();

        // Calculate win rate
        $winRate = $closedDeals->count() > 0
            ? round(($wonDeals->count() / $closedDeals->count()) * 100, 1)
            : 0;

        // Revenue Stats
        $totalRevenue = $wonDeals->sum('value');
        $pipelineValue = $openDeals->sum('value');
        $weightedPipelineValue = $openDeals->sum('weighted_value');
        $averageDealSize = $openDeals->count() > 0 ? $openDeals->avg('value') : 0;

        // Activity Stats
        $interactionsThisMonth = ContactInteraction::where('user_id', $userId)
            ->whereMonth('interaction_date', now()->month)
            ->count();

        $remindersOverdue = FollowUpReminder::where('user_id', $userId)
            ->overdue()
            ->count();

        return [
            'contacts' => [
                'total' => $totalContacts,
                'active' => $activeContacts,
                'vip' => $vipContacts,
                'needs_follow_up' => $contactsNeedingFollowUp,
            ],
            'deals' => [
                'open' => $openDeals->count(),
                'won' => $wonDeals->count(),
                'lost' => $lostDeals->count(),
                'win_rate' => $winRate,
            ],
            'revenue' => [
                'total_won' => $totalRevenue,
                'pipeline_value' => $pipelineValue,
                'weighted_value' => $weightedPipelineValue,
                'average_deal' => $averageDealSize,
            ],
            'activity' => [
                'interactions_this_month' => $interactionsThisMonth,
                'overdue_reminders' => $remindersOverdue,
            ],
        ];
    }

    public function getDealsByStage(): array
    {
        $userId = auth()->id();

        return [
            'Lead' => Deal::where('user_id', $userId)->where('stage', 'lead')->count(),
            'Qualified' => Deal::where('user_id', $userId)->where('stage', 'qualified')->count(),
            'Proposal' => Deal::where('user_id', $userId)->where('stage', 'proposal')->count(),
            'Negotiation' => Deal::where('user_id', $userId)->where('stage', 'negotiation')->count(),
            'Won' => Deal::where('user_id', $userId)->where('stage', 'won')->count(),
            'Lost' => Deal::where('user_id', $userId)->where('stage', 'lost')->count(),
        ];
    }

    public function getRevenueByMonth(): array
    {
        $userId = auth()->id();

        $revenueData = Deal::where('user_id', $userId)
            ->where('stage', 'won')
            ->whereNotNull('actual_close_date')
            ->where('actual_close_date', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(actual_close_date, "%Y-%m") as month'),
                DB::raw('SUM(value) as revenue'),
                DB::raw('COUNT(*) as deals')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $revenue = [];

        foreach ($revenueData as $data) {
            $months[] = date('M Y', strtotime($data->month . '-01'));
            $revenue[] = $data->revenue;
        }

        return [
            'months' => $months,
            'revenue' => $revenue,
        ];
    }

    public function getTopContacts(): array
    {
        return Contact::where('user_id', auth()->id())
            ->orderBy('lifetime_value', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'company' => $contact->company,
                'lifetime_value' => $contact->lifetime_value,
                'deals_count' => $contact->deals_count,
                'relationship_strength' => $contact->relationship_strength,
            ])
            ->toArray();
    }

    public function getUpcomingDeals(): array
    {
        return Deal::with('contact')
            ->where('user_id', auth()->id())
            ->open()
            ->whereBetween('expected_close_date', [now(), now()->addDays(30)])
            ->orderBy('expected_close_date')
            ->limit(5)
            ->get()
            ->map(fn($deal) => [
                'id' => $deal->id,
                'title' => $deal->title,
                'contact_name' => $deal->contact?->name,
                'value' => $deal->value,
                'stage' => $deal->stage,
                'expected_close_date' => $deal->expected_close_date?->format('M d, Y'),
                'days_until_close' => $deal->expected_close_date?->diffInDays(now()),
            ])
            ->toArray();
    }

    public function getOverdueFollowUps(): array
    {
        return Contact::with('reminders')
            ->where('user_id', auth()->id())
            ->needsFollowUp()
            ->limit(5)
            ->get()
            ->map(fn($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'company' => $contact->company,
                'next_follow_up_date' => $contact->next_follow_up_date?->format('M d, Y'),
                'days_overdue' => $contact->next_follow_up_date ? (int) round(abs($contact->next_follow_up_date->diffInDays(now()))) : 0,
            ])
            ->toArray();
    }

    public function getRecentInteractions(): array
    {
        return ContactInteraction::with('contact')
            ->where('user_id', auth()->id())
            ->orderBy('interaction_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($interaction) => [
                'id' => $interaction->id,
                'contact_name' => $interaction->contact?->name,
                'type' => $interaction->type,
                'subject' => $interaction->subject,
                'interaction_date' => $interaction->interaction_date?->format('M d, Y H:i'),
                'outcome' => $interaction->outcome,
            ])
            ->toArray();
    }

    public function getContactsByType(): array
    {
        $userId = auth()->id();

        return [
            'Client' => Contact::where('user_id', $userId)->where('type', 'client')->count(),
            'Lead' => Contact::where('user_id', $userId)->where('type', 'lead')->count(),
            'Partner' => Contact::where('user_id', $userId)->where('type', 'partner')->count(),
            'Investor' => Contact::where('user_id', $userId)->where('type', 'investor')->count(),
            'Vendor' => Contact::where('user_id', $userId)->where('type', 'vendor')->count(),
        ];
    }

    public function getRelationshipHealth(): array
    {
        $userId = auth()->id();

        return [
            'Hot' => Contact::where('user_id', $userId)->where('relationship_strength', 'hot')->count(),
            'Warm' => Contact::where('user_id', $userId)->where('relationship_strength', 'warm')->count(),
            'Cold' => Contact::where('user_id', $userId)->where('relationship_strength', 'cold')->count(),
        ];
    }
}
