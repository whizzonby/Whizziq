<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\RiskAssessment;
use Filament\Widgets\Widget;

class RiskAssessmentWidget extends Widget
{
    protected static ?int $sort = 6;

    protected string $view = 'filament.dashboard.widgets.risk-assessment-widget';

    protected int | string | array $columnSpan = 1;

    public function getRiskAssessment(): ?RiskAssessment
    {
        return RiskAssessment::where('user_id', auth()->id())
            ->latest('date')
            ->first();
    }

    public function getLoanWorthinessColor(): string
    {
        $assessment = $this->getRiskAssessment();

        if (!$assessment) {
            return 'gray';
        }

        $score = $assessment->loan_worthiness;

        if ($score >= 80) {
            return 'success';
        } elseif ($score >= 60) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    public function getLoanWorthinessPercentage(): int
    {
        $assessment = $this->getRiskAssessment();

        if (!$assessment) {
            return 0;
        }

        return (int) $assessment->loan_worthiness;
    }
}
