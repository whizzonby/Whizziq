<?php

namespace App\Filament\Dashboard\Pages;

use App\Services\AIUsageService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use UnitEnum;

class AIUsageAnalyticsPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.dashboard.pages.ai-usage-analytics-page';

    protected static ?string $navigationLabel = 'AI Usage';

    protected static ?string $title = 'AI Usage & Analytics';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;


    public ?array $usageStats = null;
    public ?array $planLimits = null;
    public ?array $canUse = null;
    public string $selectedPeriod = 'month';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $user = auth()->user();
        $usageService = app(AIUsageService::class);

        // Get date range based on selected period
        [$startDate, $endDate] = $this->getDateRange();

        // Get usage stats
        $this->usageStats = $usageService->getUsageStats($user, $startDate, $endDate);

        // Get plan limits from metadata
        $this->planLimits = $usageService->getPlanLimits($user);
        $metadata = $user->subscriptionProductMetadata();
        $this->planLimits['plan_name'] = !empty($metadata) ? 'Current Plan' : 'No Subscription';

        // Get current usage check
        $this->canUse = $usageService->canMakeRequest($user);

        // Get today's usage
        $this->canUse['today_usage'] = $usageService->getTodayUsage($user);
    }

    public function changePeriod(string $period): void
    {
        $this->selectedPeriod = $period;
        $this->loadData();
    }

    protected function getDateRange(): array
    {
        return match($this->selectedPeriod) {
            'today' => [Carbon::today()->startOfDay(), Carbon::now()],
            'week' => [Carbon::today()->startOfWeek(), Carbon::now()],
            'month' => [Carbon::today()->startOfMonth(), Carbon::now()],
            'quarter' => [Carbon::today()->startOfQuarter(), Carbon::now()],
            'year' => [Carbon::today()->startOfYear(), Carbon::now()],
            default => [Carbon::today()->startOfMonth(), Carbon::now()],
        };
    }


    public function getFeatureUsagePercentage(string $feature): float
    {
        if (!$this->usageStats || $this->usageStats['total_requests'] == 0) {
            return 0;
        }

        $featureStats = collect($this->usageStats['by_feature'])
            ->firstWhere('feature', $feature);

        if (!$featureStats) {
            return 0;
        }

        return ($featureStats->count / $this->usageStats['total_requests']) * 100;
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show if user has made at least one AI request
        return \DB::table('ai_usage_logs')
            ->where('user_id', auth()->id())
            ->exists();
    }
}
