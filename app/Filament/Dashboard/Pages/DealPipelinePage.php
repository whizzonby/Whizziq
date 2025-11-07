<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\Deal;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use UnitEnum;
use BackedEnum;

class DealPipelinePage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-view-columns';

    protected string $view = 'filament.dashboard.pages.deal-pipeline-page';

    protected static ?string $navigationLabel = 'Pipeline Board';

    protected static UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Deal Pipeline';

    public array $deals = [];
    public array $stages = [];
    public array $stageStats = [];

    public function mount(): void
    {
        $this->loadDeals();
    }

    public function loadDeals(): void
    {
        $this->stages = [
            'lead' => ['label' => 'Lead', 'color' => 'gray'],
            'qualified' => ['label' => 'Qualified', 'color' => 'blue'],
            'proposal' => ['label' => 'Proposal', 'color' => 'yellow'],
            'negotiation' => ['label' => 'Negotiation', 'color' => 'orange'],
            'won' => ['label' => 'Won', 'color' => 'success'],
            'lost' => ['label' => 'Lost', 'color' => 'danger'],
        ];

        $deals = Deal::with(['contact', 'user'])
            ->where('user_id', auth()->id())
            ->orderBy('expected_close_date', 'asc')
            ->get();

        // Group deals by stage
        $this->deals = [];
        $this->stageStats = [];

        foreach ($this->stages as $stageKey => $stageInfo) {
            $stageDeals = $deals->where('stage', $stageKey)->values();

            $this->deals[$stageKey] = $stageDeals->map(function ($deal) {
                return [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'contact_name' => $deal->contact?->name ?? 'No Contact',
                    'value' => $deal->value,
                    'weighted_value' => $deal->weighted_value,
                    'probability' => $deal->probability,
                    'currency' => $deal->currency ?? 'USD',
                    'priority' => $deal->priority,
                    'expected_close_date' => $deal->expected_close_date?->format('M d, Y'),
                    'days_in_stage' => $deal->days_in_stage,
                    'days_in_stage_color' => $deal->days_in_stage_color,
                    'is_stuck' => $deal->isStuckInStage(30),
                    'is_overdue' => $deal->expected_close_date && $deal->expected_close_date->isPast() && !in_array($deal->stage, ['won', 'lost']),
                ];
            })->toArray();

            $this->stageStats[$stageKey] = [
                'count' => $stageDeals->count(),
                'total_value' => $stageDeals->sum('value'),
                'weighted_value' => $stageDeals->sum('weighted_value'),
            ];
        }
    }

    #[On('deal-moved')]
    public function moveDeal(int $dealId, string $newStage): void
    {
        try {
            $deal = Deal::where('user_id', auth()->id())->findOrFail($dealId);

            $oldStage = $deal->stage;

            if ($newStage === 'won') {
                $deal->markAsWon();
            } elseif ($newStage === 'lost') {
                // For lost, we should ask for reason, but for quick drag-drop we'll use generic
                $deal->markAsLost('Moved to lost in pipeline');
            } else {
                $deal->moveToStage($newStage);
            }

            Notification::make()
                ->title('Deal Updated')
                ->success()
                ->body("Deal moved from {$this->stages[$oldStage]['label']} to {$this->stages[$newStage]['label']}")
                ->send();

            $this->loadDeals();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to move deal: ' . $e->getMessage())
                ->send();
        }
    }

    public function editDeal(int $dealId): void
    {
        $this->redirect(route('filament.dashboard.resources.deals.edit', ['record' => $dealId]));
    }

    public function createDeal(?string $stage = 'lead'): void
    {
        $this->redirect(route('filament.dashboard.resources.deals.create', ['stage' => $stage]));
    }

    public function getStats(): array
    {
        $userId = auth()->id();

        $openDeals = Deal::where('user_id', $userId)->open()->get();
        $wonDeals = Deal::where('user_id', $userId)->won()->get();

        return [
            'open_deals_count' => $openDeals->count(),
            'open_deals_value' => $openDeals->sum('value'),
            'open_weighted_value' => $openDeals->sum('weighted_value'),
            'won_deals_count' => $wonDeals->count(),
            'won_deals_value' => $wonDeals->sum('value'),
            'average_deal_size' => $openDeals->count() > 0 ? $openDeals->avg('value') : 0,
            'win_rate' => $this->calculateWinRate(),
        ];
    }

    protected function calculateWinRate(): float
    {
        $closedDeals = Deal::where('user_id', auth()->id())
            ->closed()
            ->count();

        if ($closedDeals === 0) {
            return 0;
        }

        $wonDeals = Deal::where('user_id', auth()->id())
            ->won()
            ->count();

        return round(($wonDeals / $closedDeals) * 100, 1);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Deal::where('user_id', auth()->id())
            ->open()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

}
