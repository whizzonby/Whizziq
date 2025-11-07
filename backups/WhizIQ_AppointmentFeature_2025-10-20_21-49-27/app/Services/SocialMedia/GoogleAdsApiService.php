<?php

namespace App\Services\SocialMedia;

use App\Models\SocialMediaConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsApiService
{
    protected string $baseUrl = 'https://googleads.googleapis.com/v14';

    /**
     * Fetch metrics from Google Ads
     */
    public function fetchMetrics(SocialMediaConnection $connection): ?array
    {
        $this->checkAndRefreshToken($connection);

        $metrics = $this->fetchCampaignMetrics($connection);

        if (!$metrics) {
            return null;
        }

        return $this->transformMetricsToStandardFormat($metrics);
    }

    /**
     * Fetch campaign metrics from Google Ads API
     */
    protected function fetchCampaignMetrics(SocialMediaConnection $connection): ?array
    {
        $customerId = $connection->account_id;
        $accessToken = $connection->access_token;

        // Google Ads uses a query language (GAQL)
        $query = "SELECT
            metrics.impressions,
            metrics.clicks,
            metrics.conversions,
            metrics.cost_micros,
            metrics.average_cpc,
            metrics.cost_per_conversion,
            metrics.conversions_value
        FROM campaign
        WHERE segments.date = TODAY";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'developer-token' => config('services.google_ads.developer_token'),
                'login-customer-id' => config('services.google_ads.manager_account_id'),
            ])->post($this->baseUrl . "/customers/{$customerId}/googleAds:searchStream", [
                'query' => $query,
            ]);

            if ($response->successful()) {
                return $this->parseGoogleAdsResponse($response->json());
            }

            Log::error('Google Ads API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Google Ads API exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse Google Ads response
     */
    protected function parseGoogleAdsResponse(array $response): array
    {
        $metrics = [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'cost' => 0,
            'average_cpc' => 0,
            'cost_per_conversion' => 0,
            'conversion_value' => 0,
        ];

        foreach ($response as $row) {
            if (isset($row['metrics'])) {
                $metrics['impressions'] += $row['metrics']['impressions'] ?? 0;
                $metrics['clicks'] += $row['metrics']['clicks'] ?? 0;
                $metrics['conversions'] += $row['metrics']['conversions'] ?? 0;
                $metrics['cost'] += ($row['metrics']['cost_micros'] ?? 0) / 1000000; // Convert micros to dollars
                $metrics['average_cpc'] = ($row['metrics']['average_cpc'] ?? 0) / 1000000;
                $metrics['cost_per_conversion'] = ($row['metrics']['cost_per_conversion'] ?? 0) / 1000000;
                $metrics['conversion_value'] += ($row['metrics']['conversions_value'] ?? 0) / 1000000;
            }
        }

        return $metrics;
    }

    /**
     * Transform Google Ads metrics to standard format
     */
    protected function transformMetricsToStandardFormat(array $metrics): array
    {
        $clicks = $metrics['clicks'] ?? 0;
        $impressions = $metrics['impressions'] ?? 0;
        $conversions = $metrics['conversions'] ?? 0;
        $cost = $metrics['cost'] ?? 0;
        $conversionValue = $metrics['conversion_value'] ?? 0;

        return [
            'platform' => 'google',
            'channel' => 'google',
            'followers' => 0, // Not applicable for Google Ads
            'impressions' => $impressions,
            'reach' => $impressions, // Approximate
            'engagement' => $clicks,
            'clicks' => $clicks,
            'engagement_rate' => 0,
            'conversion_rate' => $impressions > 0 ? round(($conversions / $impressions) * 100, 2) : 0,
            'awareness' => $impressions,
            'leads' => $clicks, // Approximate - clicks as potential leads
            'conversions' => (int) $conversions,
            'retention_count' => 0, // Would need additional tracking
            'cost_per_click' => $metrics['average_cpc'] ?? 0,
            'cost_per_conversion' => $metrics['cost_per_conversion'] ?? 0,
            'ad_spend' => $cost,
            'customer_lifetime_value' => $conversionValue > 0 && $conversions > 0
                ? round($conversionValue / $conversions, 2)
                : 0,
            'customer_acquisition_cost' => $conversions > 0 ? round($cost / $conversions, 2) : 0,
            'clv_cac_ratio' => $this->calculateCLVCACRatio($conversionValue, $conversions, $cost),
            'roi' => $cost > 0 ? round((($conversionValue - $cost) / $cost) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate CLV:CAC ratio
     */
    protected function calculateCLVCACRatio(float $conversionValue, float $conversions, float $cost): float
    {
        if ($cost == 0 || $conversions == 0) {
            return 0;
        }

        $clv = $conversionValue / $conversions;
        $cac = $cost / $conversions;

        return round($clv / $cac, 2);
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(SocialMediaConnection $connection): void
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $connection->update([
                    'access_token' => $data['access_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google token', ['error' => $e->getMessage()]);
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
