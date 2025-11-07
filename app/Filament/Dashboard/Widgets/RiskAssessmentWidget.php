<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\RiskAssessment;
use App\Services\RiskAssessmentGeneratorService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class RiskAssessmentWidget extends Widget
{
    protected static ?string $heading = '⚡ Risk Assessment';

    protected static ?int $sort = 16;


    protected string $view = 'filament.dashboard.widgets.risk-assessment-widget';

    protected int | string | array $columnSpan = 'full';

    public ?RiskAssessment $latestAssessment = null;

    public bool $isLoading = false;

    public bool $isGenerating = false;

    public function mount()
    {
        $this->loadLatestAssessment();
    }

    public function loadLatestAssessment()
    {
        $user = auth()->user();
        $this->latestAssessment = RiskAssessment::where('user_id', $user->id)
            ->latest('date')
            ->first();
    }

    public function generateRiskAssessment()
    {
        $this->isGenerating = true;

        try {
            $service = app(RiskAssessmentGeneratorService::class);
            $result = $service->generateRisks(auth()->id(), 90);

            if ($result['success']) {
                $this->loadLatestAssessment();

                \Filament\Notifications\Notification::make()
                    ->title('Risk Assessment Complete!')
                    ->body("Risk Score: {$result['risk_score']}/100 ({$result['risk_level']}). Identified {$result['risk_factors_count']} risk factors.")
                    ->success()
                    ->send();

                // Clear AI insights cache to refresh with new risk data
                $cacheKey = "ai_insights_" . auth()->id() . "_" . now()->format('Y-m-d-H');
                Cache::forget($cacheKey);
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Cannot Assess Risks')
                    ->body($result['message'] ?? 'Not enough financial data to assess risks.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Assessment Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }

    public function getRiskAssessment(): ?RiskAssessment
    {
        return $this->latestAssessment;
    }

    public function getRiskScoreColor(): string
    {
        if (!$this->latestAssessment) return 'gray';

        return match(true) {
            $this->latestAssessment->risk_score < 25 => 'success',
            $this->latestAssessment->risk_score < 50 => 'warning',
            $this->latestAssessment->risk_score < 75 => 'danger',
            default => 'danger',
        };
    }

    public function getLoanWorthinessColor(): string
    {
        if (!$this->latestAssessment) return 'gray';

        return match($this->latestAssessment->loan_worthiness_level) {
            'poor' => 'danger',
            'fair' => 'warning',
            'good' => 'success',
            'excellent' => 'success',
            default => 'gray',
        };
    }

    public function getLoanWorthinessPercentage(): int
    {
        if (!$this->latestAssessment) return 0;

        return (int) $this->latestAssessment->loan_worthiness;
    }

    public function getRiskLevelIcon(): string
    {
        if (!$this->latestAssessment) return 'heroicon-o-shield-exclamation';

        return match($this->latestAssessment->risk_level) {
            'low' => 'heroicon-o-shield-check',
            'moderate' => 'heroicon-o-shield-exclamation',
            'high' => 'heroicon-o-exclamation-triangle',
            'critical' => 'heroicon-o-exclamation-circle',
            default => 'heroicon-o-shield-exclamation',
        };
    }

    public function isStale(): bool
    {
        if (!$this->latestAssessment) return true;

        $assessmentAge = Carbon::parse($this->latestAssessment->date)->diffInDays(Carbon::today());
        return $assessmentAge > 30; // Consider stale after 30 days
    }

    public function getAssessmentAge(): string
    {
        if (!$this->latestAssessment) return 'No assessment available';

        return Carbon::parse($this->latestAssessment->date)->diffForHumans();
    }

    public function getHighSeverityRisks(): array
    {
        if (!$this->latestAssessment || !is_array($this->latestAssessment->risk_factors)) {
            return [];
        }

        return array_filter($this->latestAssessment->risk_factors, function($risk) {
            return ($risk['severity'] ?? 'medium') === 'high';
        });
    }

    public function getRiskCountBySeverity(string $severity): int
    {
        if (!$this->latestAssessment || !is_array($this->latestAssessment->risk_factors)) {
            return 0;
        }

        return count(array_filter($this->latestAssessment->risk_factors, function($risk) use ($severity) {
            return ($risk['severity'] ?? 'medium') === $severity;
        }));
    }

    public function getSeverityColor(string $severity): string
    {
        return match($severity) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'gray',
        };
    }
}
