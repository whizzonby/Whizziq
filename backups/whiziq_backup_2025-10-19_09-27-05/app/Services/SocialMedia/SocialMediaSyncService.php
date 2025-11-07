<?php

namespace App\Services\SocialMedia;

use App\Models\MarketingMetric;
use App\Models\SocialMediaConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SocialMediaSyncService
{
    protected MetaApiService $metaApi;
    protected GoogleAdsApiService $googleAdsApi;
    protected LinkedInAdsApiService $linkedInAdsApi;

    public function __construct(
        MetaApiService $metaApi,
        GoogleAdsApiService $googleAdsApi,
        LinkedInAdsApiService $linkedInAdsApi
    ) {
        $this->metaApi = $metaApi;
        $this->googleAdsApi = $googleAdsApi;
        $this->linkedInAdsApi = $linkedInAdsApi;
    }

    /**
     * Sync all active connections for a user
     */
    public function syncAllForUser(int $userId): array
    {
        $connections = SocialMediaConnection::where('user_id', $userId)
            ->active()
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($connections as $connection) {
            try {
                $this->syncConnection($connection);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'platform' => $connection->platform,
                    'message' => $e->getMessage(),
                ];
                Log::error('Social media sync failed', [
                    'platform' => $connection->platform,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Sync a specific connection
     */
    public function syncConnection(SocialMediaConnection $connection): void
    {
        $connection->startSync();

        try {
            $data = $this->fetchDataFromPlatform($connection);

            if ($data) {
                $this->saveMarketingMetrics($connection, $data);
                $connection->markSyncSuccess();
            } else {
                throw new \Exception('No data returned from platform');
            }
        } catch (\Exception $e) {
            $connection->markSyncFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch data from the appropriate platform
     */
    public function fetchDataFromPlatform(SocialMediaConnection $connection): ?array
    {
        return match($connection->platform) {
            'facebook', 'instagram' => $this->metaApi->fetchMetrics($connection),
            'google_ads', 'google' => $this->googleAdsApi->fetchMetrics($connection),
            'linkedin' => $this->linkedInAdsApi->fetchMetrics($connection),
            default => throw new \Exception("Unsupported platform: {$connection->platform}"),
        };
    }

    /**
     * Save fetched metrics to database
     */
    protected function saveMarketingMetrics(SocialMediaConnection $connection, array $data): void
    {
        $today = Carbon::today();

        // Check if metric already exists for today
        $metric = MarketingMetric::firstOrNew([
            'user_id' => $connection->user_id,
            'date' => $today,
            'platform' => $connection->platform,
            'channel' => $this->determineChannel($connection->platform),
        ]);

        // Merge fetched data with existing or set new data
        $metric->fill($data);
        $metric->save();
    }

    /**
     * Determine channel from platform
     */
    protected function determineChannel(string $platform): string
    {
        return match($platform) {
            'facebook' => 'facebook',
            'instagram' => 'organic', // or 'facebook' if running Instagram ads through Facebook
            'google_ads' => 'google',
            'linkedin' => 'linkedin',
            default => 'organic',
        };
    }

    /**
     * Check if connection needs token refresh
     */
    public function checkAndRefreshToken(SocialMediaConnection $connection): void
    {
        if (!$connection->isTokenExpired()) {
            return;
        }

        match($connection->platform) {
            'facebook', 'instagram' => $this->metaApi->refreshAccessToken($connection),
            'google_ads' => $this->googleAdsApi->refreshAccessToken($connection),
            'linkedin' => $this->linkedInAdsApi->refreshAccessToken($connection),
            default => null,
        };
    }
}
