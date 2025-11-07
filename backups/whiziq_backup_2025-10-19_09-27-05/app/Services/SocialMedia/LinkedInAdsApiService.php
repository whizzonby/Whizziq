<?php

namespace App\Services\SocialMedia;

use App\Models\SocialMediaConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInAdsApiService
{
    protected string $baseUrl = 'https://api.linkedin.com/rest';

    /**
     * Fetch metrics from LinkedIn Ads
     */
    public function fetchMetrics(SocialMediaConnection $connection): ?array
    {
        $this->checkAndRefreshToken($connection);

        $metrics = $this->fetchAdAnalytics($connection);

        if (!$metrics) {
            return null;
        }

        return $this->transformMetricsToStandardFormat($metrics);
    }

    /**
     * Fetch ad analytics from LinkedIn Marketing API
     */
    protected function fetchAdAnalytics(SocialMediaConnection $connection): ?array
    {
        $accountId = $connection->account_id;
        $accessToken = $connection->access_token;

        $dateRange = [
            'start' => now()->subDay()->startOfDay()->toIso8601String(),
            'end' => now()->endOfDay()->toIso8601String(),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'LinkedIn-Version' => '202310',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get($this->baseUrl . '/adAnalytics', [
                'q' => 'analytics',
                'pivot' => 'ACCOUNT',
                'accounts' => "urn:li:sponsoredAccount:{$accountId}",
                'dateRange.start.day' => now()->subDay()->day,
                'dateRange.start.month' => now()->subDay()->month,
                'dateRange.start.year' => now()->subDay()->year,
                'dateRange.end.day' => now()->day,
                'dateRange.end.month' => now()->month,
                'dateRange.end.year' => now()->year,
                'fields' => implode(',', [
                    'impressions',
                    'clicks',
                    'costInLocalCurrency',
                    'externalWebsiteConversions',
                    'externalWebsitePostClickConversions',
                    'oneClickLeads',
                    'approximateUniqueImpressions',
                    'shares',
                    'likes',
                    'comments',
                ]),
            ]);

            if ($response->successful()) {
                return $this->parseLinkedInResponse($response->json());
            }

            Log::error('LinkedIn Ads API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('LinkedIn Ads API exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse LinkedIn response
     */
    protected function parseLinkedInResponse(array $response): array
    {
        $metrics = [
            'impressions' => 0,
            'clicks' => 0,
            'cost' => 0,
            'conversions' => 0,
            'leads' => 0,
            'reach' => 0,
            'engagement' => 0,
        ];

        $elements = $response['elements'] ?? [];

        foreach ($elements as $element) {
            $metrics['impressions'] += $element['impressions'] ?? 0;
            $metrics['clicks'] += $element['clicks'] ?? 0;
            $metrics['cost'] += $element['costInLocalCurrency'] ?? 0;
            $metrics['conversions'] += $element['externalWebsiteConversions'] ?? 0;
            $metrics['conversions'] += $element['externalWebsitePostClickConversions'] ?? 0;
            $metrics['leads'] += $element['oneClickLeads'] ?? 0;
            $metrics['reach'] += $element['approximateUniqueImpressions'] ?? 0;
            $metrics['engagement'] += ($element['shares'] ?? 0) + ($element['likes'] ?? 0) + ($element['comments'] ?? 0);
        }

        return $metrics;
    }

    /**
     * Transform LinkedIn metrics to standard format
     */
    protected function transformMetricsToStandardFormat(array $metrics): array
    {
        $clicks = $metrics['clicks'] ?? 0;
        $impressions = $metrics['impressions'] ?? 0;
        $conversions = $metrics['conversions'] ?? 0;
        $leads = $metrics['leads'] ?? 0;
        $cost = $metrics['cost'] ?? 0;
        $reach = $metrics['reach'] ?? 0;
        $engagement = $metrics['engagement'] ?? 0;

        // Estimate CLV (would need additional tracking for accurate value)
        $estimatedCLV = $conversions > 0 ? 850 : 0; // Placeholder - should be configured per business

        return [
            'platform' => 'linkedin',
            'channel' => 'linkedin',
            'followers' => 0, // Would need Organization API for follower count
            'impressions' => $impressions,
            'reach' => $reach,
            'engagement' => $engagement,
            'clicks' => $clicks,
            'engagement_rate' => $impressions > 0 ? round(($engagement / $impressions) * 100, 2) : 0,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            'awareness' => $impressions,
            'leads' => $leads + $clicks, // Sum of form leads and clicks
            'conversions' => (int) $conversions,
            'retention_count' => 0, // Would need additional tracking
            'cost_per_click' => $clicks > 0 ? round($cost / $clicks, 2) : 0,
            'cost_per_conversion' => $conversions > 0 ? round($cost / $conversions, 2) : 0,
            'ad_spend' => $cost,
            'customer_lifetime_value' => $estimatedCLV,
            'customer_acquisition_cost' => $conversions > 0 ? round($cost / $conversions, 2) : 0,
            'clv_cac_ratio' => $this->calculateCLVCACRatio($estimatedCLV, $conversions, $cost),
            'roi' => $this->calculateROI($estimatedCLV, $conversions, $cost),
        ];
    }

    /**
     * Calculate CLV:CAC ratio
     */
    protected function calculateCLVCACRatio(float $clv, float $conversions, float $cost): float
    {
        if ($cost == 0 || $conversions == 0) {
            return 0;
        }

        $cac = $cost / $conversions;

        return round($clv / $cac, 2);
    }

    /**
     * Calculate ROI
     */
    protected function calculateROI(float $clv, float $conversions, float $cost): float
    {
        if ($cost == 0) {
            return 0;
        }

        $revenue = $clv * $conversions;

        return round((($revenue - $cost) / $cost) * 100, 2);
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(SocialMediaConnection $connection): void
    {
        try {
            $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $connection->refresh_token,
                'client_id' => config('services.linkedin-openid.client_id'),
                'client_secret' => config('services.linkedin-openid.client_secret'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $connection->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $connection->refresh_token,
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 5184000),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh LinkedIn token', ['error' => $e->getMessage()]);
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
