<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected string $model = 'gpt-4';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
    }

    /**
     * Generate a chat completion using GPT-4
     */
    public function chat(array $messages, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key is not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $options['model'] ?? $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 1000,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Analyze business data and generate insights
     */
    public function generateBusinessInsights(array $data): ?string
    {
        $prompt = $this->buildBusinessInsightsPrompt($data);

        return $this->chat([
            [
                'role' => 'system',
                'content' => 'You are a business analytics AI assistant. Analyze the provided business data and generate actionable insights. Focus on trends, anomalies, opportunities, and risks. Be concise and specific.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ]);
    }

    /**
     * Process natural language query about business data
     */
    public function processNaturalLanguageQuery(string $query, array $availableData): ?string
    {
        $dataContext = json_encode($availableData, JSON_PRETTY_PRINT);

        return $this->chat([
            [
                'role' => 'system',
                'content' => 'You are a business analytics AI assistant. Answer questions about business data based on the provided context. Be specific and data-driven in your responses.',
            ],
            [
                'role' => 'user',
                'content' => "Available data:\n{$dataContext}\n\nQuestion: {$query}",
            ],
        ], [
            'max_tokens' => 500,
        ]);
    }

    /**
     * Detect anomalies in business metrics
     */
    public function detectAnomalies(array $historicalData, array $currentData): ?array
    {
        $prompt = "Analyze the following business metrics data and identify any anomalies or unusual patterns.\n\n";
        $prompt .= "Historical data:\n" . json_encode($historicalData, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Current data:\n" . json_encode($currentData, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Respond with a JSON array of anomalies. Each anomaly should have 'metric', 'severity' (low/medium/high), 'description', and 'recommendation' fields.";

        $response = $this->chat([
            [
                'role' => 'system',
                'content' => 'You are an anomaly detection AI. Analyze business metrics and identify unusual patterns. Respond only with valid JSON.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.3,
        ]);

        if ($response) {
            try {
                return json_decode($response, true);
            } catch (\Exception $e) {
                Log::error('Failed to parse anomaly detection response', [
                    'response' => $response,
                ]);
            }
        }

        return null;
    }

    /**
     * Generate predictive forecast
     */
    public function generateForecast(array $historicalData, int $days = 30): ?string
    {
        $dataJson = json_encode($historicalData, JSON_PRETTY_PRINT);

        return $this->chat([
            [
                'role' => 'system',
                'content' => 'You are a predictive analytics AI. Generate forecasts based on historical business data. Provide specific predictions with confidence levels.',
            ],
            [
                'role' => 'user',
                'content' => "Based on this historical data:\n{$dataJson}\n\nGenerate a {$days}-day forecast for key metrics including revenue, expenses, and cash flow. Include best case, worst case, and most likely scenarios.",
            ],
        ], [
            'max_tokens' => 800,
        ]);
    }

    /**
     * Build prompt for business insights
     */
    protected function buildBusinessInsightsPrompt(array $data): string
    {
        $prompt = "Analyze the following business data and provide actionable insights:\n\n";

        foreach ($data as $category => $values) {
            $prompt .= ucwords(str_replace('_', ' ', $category)) . ":\n";
            $prompt .= json_encode($values, JSON_PRETTY_PRINT) . "\n\n";
        }

        $prompt .= "Please provide:\n";
        $prompt .= "1. Key trends and patterns\n";
        $prompt .= "2. Potential risks or concerns\n";
        $prompt .= "3. Opportunities for improvement\n";
        $prompt .= "4. Specific actionable recommendations\n";

        return $prompt;
    }
}
