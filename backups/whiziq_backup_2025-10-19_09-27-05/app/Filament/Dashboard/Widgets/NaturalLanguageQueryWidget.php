<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\BusinessMetric;
use App\Services\OpenAIService;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class NaturalLanguageQueryWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.dashboard.widgets.natural-language-query-widget';

    protected int | string | array $columnSpan = 'full';

    public string $query = '';

    public ?string $response = null;

    public bool $isProcessing = false;

    public function processQuery()
    {
        if (empty($this->query)) {
            $this->response = 'Please enter a question about your business data.';
            return;
        }

        $this->isProcessing = true;

        try {
            $openAI = app(OpenAIService::class);

            // Gather available business data
            $user = auth()->user();
            $latestMetrics = BusinessMetric::where('user_id', $user->id)
                ->latest('date')
                ->first();

            $availableData = [
                'latest_metrics' => $latestMetrics ? [
                    'date' => $latestMetrics->date->format('Y-m-d'),
                    'revenue' => $latestMetrics->revenue,
                    'profit' => $latestMetrics->profit,
                    'expenses' => $latestMetrics->expenses,
                    'cash_flow' => $latestMetrics->cash_flow,
                ] : null,
                'metrics_count' => BusinessMetric::where('user_id', $user->id)->count(),
            ];

            $this->response = $openAI->processNaturalLanguageQuery($this->query, $availableData);

            if (!$this->response) {
                $this->response = 'Sorry, I couldn\'t process your query at this time. Please make sure your OpenAI API key is configured in the .env file (OPENAI_API_KEY).';
            }
        } catch (\Exception $e) {
            $this->response = 'An error occurred while processing your query: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function clearQuery()
    {
        $this->query = '';
        $this->response = null;
    }

    public function getSuggestedQuestions(): array
    {
        return [
            'What is my current financial status?',
            'How has my revenue changed over time?',
            'What are my biggest expenses?',
            'Is my cash flow healthy?',
            'What trends do you see in my business data?',
        ];
    }
}
