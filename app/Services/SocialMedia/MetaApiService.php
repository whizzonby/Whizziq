<?php

namespace App\Services\SocialMedia;

use App\Models\SocialMediaConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaApiService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';

    /**
     * Fetch metrics from Meta (Facebook/Instagram)
     */
    public function fetchMetrics(SocialMediaConnection $connection): ?array
    {
        $this->checkAndRefreshToken($connection);

        $metrics = $this->fetchInsights($connection);

        if (!$metrics) {
            return null;
        }

        return $this->transformMetricsToStandardFormat($metrics, $connection->platform);
    }

    /**
     * Fetch insights from Meta Graph API
     */
    protected function fetchInsights(SocialMediaConnection $connection): ?array
    {
        $accountId = $connection->account_id;
        $accessToken = $connection->access_token;

        // Determine endpoint based on platform
        $endpoint = $connection->platform === 'facebook'
            ? "/{$accountId}/insights"
            : "/{$accountId}/insights"; // Instagram uses same structure

        try {
            $response = Http::get($this->baseUrl . $endpoint, [
                'access_token' => $accessToken,
                'metric' => implode(',', [
                    'page_impressions',
                    'page_engaged_users',
                    'page_post_engagements',
                    'page_fans',
                    'page_video_views',
                    'page_actions_post_reactions_total',
                ]),
                'period' => 'day',
                'since' => now()->subDay()->format('Y-m-d'),
                'until' => now()->format('Y-m-d'),
            ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Meta API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Meta API exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Transform Meta metrics to standard format
     */
    protected function transformMetricsToStandardFormat(array $metrics, string $platform): array
    {
        // Extract values from metrics array
        $data = [];
        foreach ($metrics as $metric) {
            $name = $metric['name'] ?? null;
            $value = $metric['values'][0]['value'] ?? 0;

            if ($name) {
                $data[$name] = $value;
            }
        }

        // Map to our standard format
        return [
            'platform' => $platform,
            'followers' => $data['page_fans'] ?? 0,
            'impressions' => $data['page_impressions'] ?? 0,
            'reach' => $data['page_impressions'] ?? 0, // Approximate
            'engagement' => $data['page_post_engagements'] ?? 0,
            'clicks' => $data['page_engaged_users'] ?? 0,
            'engagement_rate' => $this->calculateEngagementRate(
                $data['page_post_engagements'] ?? 0,
                $data['page_fans'] ?? 1
            ),
            // These would come from Ads API if running ads
            'awareness' => $data['page_impressions'] ?? 0,
            'leads' => 0, // Would need Ads API or lead forms integration
            'conversions' => 0, // Would need Ads API or pixel data
            'retention_count' => 0,
            'cost_per_click' => 0,
            'cost_per_conversion' => 0,
            'ad_spend' => 0,
            'conversion_rate' => 0,
            'customer_lifetime_value' => 0,
            'customer_acquisition_cost' => 0,
            'clv_cac_ratio' => 0,
            'roi' => 0,
        ];
    }

    /**
     * Calculate engagement rate
     */
    protected function calculateEngagementRate(int $engagement, int $followers): float
    {
        if ($followers == 0) {
            return 0;
        }

        return round(($engagement / $followers) * 100, 2);
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(SocialMediaConnection $connection): void
    {
        try {
            $response = Http::get($this->baseUrl . '/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'fb_exchange_token' => $connection->access_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $connection->update([
                    'access_token' => $data['access_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 5184000), // Default 60 days
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh Meta token', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check and refresh token if needed
     */
    protected function checkAndRefreshToken(SocialMediaConnection $connection): void
    {
        if ($connection->isTokenExpired()) {
            $this->refreshAccessToken($connection);
        }
    }
}
