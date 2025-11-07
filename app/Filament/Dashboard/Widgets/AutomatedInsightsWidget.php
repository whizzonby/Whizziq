<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\SwotAnalysis;
use App\Services\OpenAIService;
use App\Services\FinancialMetricsCalculator;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class AutomatedInsightsWidget extends Widget
{
    protected static ?string $heading = '💡 Automated Insights';

    protected static ?int $sort = 15;


    protected string $view = 'filament.dashboard.widgets.automated-insights-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $insights = null;

    public bool $isLoading = false;

    public function mount()
    {
        $this->generateInsights();
    }

    public function generateInsights()
    {
        $this->isLoading = true;

        try {
            $user = auth()->user();
            $cacheKey = "automated_insights_{$user->id}_" . now()->format('Y-m-d-H');

            // Cache for 2 hours to prevent duplicate AI calls on page reload and reduce API costs
            $this->insights = Cache::remember($cacheKey, 7200, function () use ($user) {
                $openAI = app(OpenAIService::class);

                // Gather comprehensive business data
                $data = $this->gatherBusinessData($user);

                // Generate AI insights
                $response = $openAI->generateBusinessInsights($data);

                if ($response) {
                    return $this->parseInsights($response);
                } else {
                    return $this->getFallbackInsights($data);
                }
            });
        } catch (\Exception $e) {
            $this->insights = [
                [
                    'type' => 'info',
                    'title' => 'AI Insights Unavailable',
                    'description' => 'Please configure your OpenAI API key in the .env file (OPENAI_API_KEY) to enable AI-powered insights.',
                    'icon' => 'heroicon-o-information-circle',
                ],
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    protected function gatherBusinessData($user): array
    {
        $calculator = app(FinancialMetricsCalculator::class);
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Get current and previous metrics
        $currentMetrics = $calculator->getCurrentMonthMetrics($user);
        $previousMetrics = $calculator->getLastMonthMetrics($user);

        // Build metrics array for last 30 days
        $metrics = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayMetrics = $calculator->calculateMetricsForPeriod($user, $date->copy()->startOfDay(), $date->copy()->endOfDay());
            $metrics[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => $dayMetrics['revenue'],
                'profit' => $dayMetrics['profit'],
                'expenses' => $dayMetrics['expenses'],
                'cash_flow' => $dayMetrics['cash_flow'],
            ];
        }

        return [
            'metrics' => $metrics,
            'top_expenses' => Expense::where('user_id', $user->id)
                ->where('date', '>=', $startOfMonth)
                ->orderBy('amount', 'desc')
                ->take(5)
                ->get()
                ->map(fn($e) => [
                    'category' => $e->category,
                    'amount' => $e->amount,
                ])
                ->toArray(),
            'revenue_sources' => RevenueSource::where('user_id', $user->id)
                ->where('date', '>=', $startOfMonth)
                ->get()
                ->map(fn($r) => [
                    'source' => $r->source,
                    'amount' => $r->amount,
                    'percentage' => $r->percentage,
                ])
                ->toArray(),
            'swot_summary' => [
                'strengths' => SwotAnalysis::where('user_id', $user->id)
                    ->where('type', 'strength')
                    ->count(),
                'weaknesses' => SwotAnalysis::where('user_id', $user->id)
                    ->where('type', 'weakness')
                    ->count(),
                'opportunities' => SwotAnalysis::where('user_id', $user->id)
                    ->where('type', 'opportunity')
                    ->count(),
                'threats' => SwotAnalysis::where('user_id', $user->id)
                    ->where('type', 'threat')
                    ->count(),
            ],
        ];
    }

    protected function parseInsights(string $response): array
    {
        // Parse AI response into structured insights
        $lines = explode("\n", trim($response));
        $insights = [];
        $currentInsight = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if it's a header/category
            if (preg_match('/^#+\s*(.+)$/', $line, $matches) ||
                preg_match('/^(\d+\.|[-*])\s*(.+)$/', $line, $matches)) {

                if ($currentInsight) {
                    $insights[] = $currentInsight;
                }

                $title = $matches[1] ?? $matches[2] ?? $line;
                $currentInsight = [
                    'type' => $this->determineInsightType($title),
                    'title' => $title,
                    'description' => '',
                    'icon' => $this->getIconForType($this->determineInsightType($title)),
                ];
            } else {
                if ($currentInsight) {
                    $currentInsight['description'] .= $line . ' ';
                }
            }
        }

        if ($currentInsight) {
            $insights[] = $currentInsight;
        }

        return !empty($insights) ? $insights : $this->parsePlainText($response);
    }

    protected function parsePlainText(string $response): array
    {
        return [
            [
                'type' => 'info',
                'title' => 'Business Insights',
                'description' => $response,
                'icon' => 'heroicon-o-light-bulb',
            ],
        ];
    }

    protected function determineInsightType(string $text): string
    {
        $text = strtolower($text);

        if (str_contains($text, 'risk') || str_contains($text, 'concern') || str_contains($text, 'warning')) {
            return 'warning';
        }

        if (str_contains($text, 'opportunity') || str_contains($text, 'growth') || str_contains($text, 'recommendation')) {
            return 'success';
        }

        if (str_contains($text, 'trend') || str_contains($text, 'pattern')) {
            return 'info';
        }

        return 'default';
    }

    protected function getIconForType(string $type): string
    {
        return match($type) {
            'warning' => 'heroicon-o-exclamation-triangle',
            'success' => 'heroicon-o-light-bulb',
            'info' => 'heroicon-o-chart-bar',
            default => 'heroicon-o-information-circle',
        };
    }

    protected function getFallbackInsights(array $data): array
    {
        $insights = [];

        // Basic insights without AI
        if (!empty($data['metrics'])) {
            $latest = $data['metrics'][0] ?? null;
            if ($latest) {
                if ($latest['cash_flow'] < 10000) {
                    $insights[] = [
                        'type' => 'warning',
                        'title' => 'Low Cash Flow Alert',
                        'description' => 'Your current cash flow is below $10,000. Consider reviewing cost-cutting options.',
                        'icon' => 'heroicon-o-exclamation-triangle',
                    ];
                }

                if ($latest['profit'] > $latest['revenue'] * 0.2) {
                    $insights[] = [
                        'type' => 'success',
                        'title' => 'Healthy Profit Margin',
                        'description' => 'Your profit margin is above 20%, which indicates good financial health.',
                        'icon' => 'heroicon-o-check-circle',
                    ];
                }
            }
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Welcome to AI Insights',
                'description' => 'Configure your OpenAI API key to receive AI-powered business insights and recommendations.',
                'icon' => 'heroicon-o-information-circle',
            ];
        }

        return $insights;
    }

    public function refreshInsights()
    {
        // Clear cache and regenerate
        $user = auth()->user();
        $cacheKey = "automated_insights_{$user->id}_" . now()->format('Y-m-d-H');
        Cache::forget($cacheKey);
        
        $this->generateInsights();
    }
}
