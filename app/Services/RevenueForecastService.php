<?php

namespace App\Services;

use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueForecastService
{
    /**
     * Get revenue forecast for the next N months
     */
    public function getForecast(int $userId, int $months = 6): array
    {
        $forecasts = [];

        for ($i = 0; $i < $months; $i++) {
            $month = now()->addMonths($i);
            $forecasts[] = [
                'month' => $month->format('M Y'),
                'month_key' => $month->format('Y-m'),
                'committed' => $this->getCommittedRevenue($userId, $month),
                'best_case' => $this->getBestCaseRevenue($userId, $month),
                'weighted' => $this->getWeightedRevenue($userId, $month),
                'historical_avg' => $this->getHistoricalAverage($userId, $month),
            ];
        }

        return $forecasts;
    }

    /**
     * Committed revenue (deals at 80%+ probability or negotiation stage)
     */
    protected function getCommittedRevenue(int $userId, Carbon $month): float
    {
        return Deal::where('user_id', $userId)
            ->open()
            ->where('expected_close_date', '>=', $month->copy()->startOfMonth())
            ->where('expected_close_date', '<=', $month->copy()->endOfMonth())
            ->where(function($query) {
                $query->where('probability', '>=', 80)
                    ->orWhere('stage', 'negotiation');
            })
            ->sum('value');
    }

    /**
     * Best case revenue (all open deals)
     */
    protected function getBestCaseRevenue(int $userId, Carbon $month): float
    {
        return Deal::where('user_id', $userId)
            ->open()
            ->where('expected_close_date', '>=', $month->copy()->startOfMonth())
            ->where('expected_close_date', '<=', $month->copy()->endOfMonth())
            ->sum('value');
    }

    /**
     * Weighted revenue (value * probability)
     */
    protected function getWeightedRevenue(int $userId, Carbon $month): float
    {
        $deals = Deal::where('user_id', $userId)
            ->open()
            ->where('expected_close_date', '>=', $month->copy()->startOfMonth())
            ->where('expected_close_date', '<=', $month->copy()->endOfMonth())
            ->get();

        return $deals->sum(function($deal) {
            return ($deal->value * $deal->probability) / 100;
        });
    }

    /**
     * Historical average for this month (based on past years)
     */
    protected function getHistoricalAverage(int $userId, Carbon $month): float
    {
        $monthNumber = $month->month;

        $historical = Deal::where('user_id', $userId)
            ->where('stage', 'won')
            ->whereNotNull('actual_close_date')
            ->whereMonth('actual_close_date', $monthNumber)
            ->where('actual_close_date', '<', now())
            ->select(
                DB::raw('YEAR(actual_close_date) as year'),
                DB::raw('SUM(value) as total')
            )
            ->groupBy('year')
            ->get();

        if ($historical->isEmpty()) {
            return 0;
        }

        return $historical->avg('total');
    }

    /**
     * Get win rate statistics
     */
    public function getWinRateStats(int $userId): array
    {
        $total = Deal::where('user_id', $userId)->closed()->count();
        $won = Deal::where('user_id', $userId)->won()->count();
        $lost = Deal::where('user_id', $userId)->lost()->count();

        $winRate = $total > 0 ? ($won / $total) * 100 : 0;

        return [
            'total_closed' => $total,
            'won' => $won,
            'lost' => $lost,
            'win_rate' => round($winRate, 1),
        ];
    }

    /**
     * Get average deal cycle time (days from creation to close)
     */
    public function getAverageCycleTime(int $userId): ?int
    {
        $deals = Deal::where('user_id', $userId)
            ->won()
            ->whereNotNull('actual_close_date')
            ->get();

        if ($deals->isEmpty()) {
            return null;
        }

        $totalDays = $deals->sum(function($deal) {
            return $deal->created_at->diffInDays($deal->actual_close_date);
        });

        return round($totalDays / $deals->count());
    }

    /**
     * Get deals by stage distribution
     */
    public function getStageDistribution(int $userId): array
    {
        $stages = [
            'lead' => ['label' => 'Lead', 'count' => 0, 'value' => 0],
            'qualified' => ['label' => 'Qualified', 'count' => 0, 'value' => 0],
            'proposal' => ['label' => 'Proposal', 'count' => 0, 'value' => 0],
            'negotiation' => ['label' => 'Negotiation', 'count' => 0, 'value' => 0],
        ];

        $deals = Deal::where('user_id', $userId)
            ->open()
            ->select('stage', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as value'))
            ->groupBy('stage')
            ->get();

        foreach ($deals as $deal) {
            if (isset($stages[$deal->stage])) {
                $stages[$deal->stage]['count'] = $deal->count;
                $stages[$deal->stage]['value'] = $deal->value;
            }
        }

        return $stages;
    }

    /**
     * Get quarterly forecast
     */
    public function getQuarterlyForecast(int $userId): array
    {
        $quarters = [];

        for ($q = 0; $q < 4; $q++) {
            $quarterStart = now()->addQuarters($q)->firstOfQuarter();
            $quarterEnd = now()->addQuarters($q)->lastOfQuarter();

            $weighted = Deal::where('user_id', $userId)
                ->open()
                ->whereBetween('expected_close_date', [$quarterStart, $quarterEnd])
                ->get()
                ->sum(function($deal) {
                    return ($deal->value * $deal->probability) / 100;
                });

            $committed = Deal::where('user_id', $userId)
                ->open()
                ->whereBetween('expected_close_date', [$quarterStart, $quarterEnd])
                ->where('probability', '>=', 80)
                ->sum('value');

            $quarters[] = [
                'quarter' => 'Q' . ($quarterStart->quarter) . ' ' . $quarterStart->year,
                'quarter_key' => $quarterStart->format('Y-Q') . $quarterStart->quarter,
                'weighted' => $weighted,
                'committed' => $committed,
                'start_date' => $quarterStart->format('M d'),
                'end_date' => $quarterEnd->format('M d, Y'),
            ];
        }

        return $quarters;
    }

    /**
     * Get top deals contributing to forecast
     */
    public function getTopForecastDeals(int $userId, int $limit = 10): Collection
    {
        return Deal::where('user_id', $userId)
            ->open()
            ->where('expected_close_date', '>=', now())
            ->where('expected_close_date', '<=', now()->addMonths(3))
            ->orderByDesc('value')
            ->limit($limit)
            ->with('contact')
            ->get();
    }
}
