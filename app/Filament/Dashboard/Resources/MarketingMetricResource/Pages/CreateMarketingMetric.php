<?php

namespace App\Filament\Dashboard\Resources\MarketingMetricResource\Pages;

use App\Filament\Dashboard\Resources\MarketingMetricResource;
use App\Models\MarketingMetric;
use App\Models\SocialMediaConnection;
use App\Services\SocialMedia\SocialMediaSyncService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CreateMarketingMetric extends CreateRecord
{
    protected static string $resource = MarketingMetricResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return MarketingMetricResource::getUrl('index');
    }

    public function form(Schema $schema): Schema
    {
        // Show ONLY the Quick Import - no manual forms
        return $schema->components([
            Section::make('Import Marketing Data')
                ->description('Connect your advertising accounts to automatically import campaign metrics and performance data')
                ->schema([
                    View::make('filament.dashboard.resources.marketing-metric-resource.quick-import')
                        ->viewData([
                            'connections' => $this->getConnections(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function getConnections()
    {
        return SocialMediaConnection::where('user_id', auth()->id())
            ->get()
            ->keyBy('platform');
    }

    // OAuth Connection Methods

    /**
     * Connect to Meta Ads Manager (Facebook & Instagram)
     */
    public function connectMeta()
    {
        $clientId = config('services.facebook.client_id');
        $redirectUri = route('marketing.oauth.callback', ['platform' => 'facebook']);
        $scopes = 'pages_read_engagement,pages_show_list,read_insights,ads_read,ads_management';

        $url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    public function connectGoogle()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = route('marketing.oauth.callback', ['platform' => 'google']);
        $scopes = 'https://www.googleapis.com/auth/adwords';

        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    public function connectLinkedIn()
    {
        $clientId = config('services.linkedin-openid.client_id');
        $redirectUri = route('marketing.oauth.callback', ['platform' => 'linkedin']);
        $scopes = 'r_ads,r_ads_reporting,rw_ads';

        $url = "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    /**
     * Connect to TikTok Ads
     */
    public function connectTikTok()
    {
        $clientId = config('services.tiktok.client_id');
        $redirectUri = route('marketing.oauth.callback', ['platform' => 'tiktok']);

        if (!$clientId) {
            $this->showComingSoon('TikTok Ads');
            return;
        }

        $url = "https://business-api.tiktok.com/portal/auth?" . http_build_query([
            'app_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    /**
     * Connect to X (Twitter) Ads
     */
    public function connectTwitter()
    {
        $clientId = config('services.twitter.client_id');
        $redirectUri = route('marketing.oauth.callback', ['platform' => 'twitter']);

        if (!$clientId) {
            $this->showComingSoon('X (Twitter) Ads');
            return;
        }

        $url = "https://twitter.com/i/oauth2/authorize?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'tweet.read users.read offline.access',
            'response_type' => 'code',
            'state' => csrf_token(),
            'code_challenge' => 'challenge',
            'code_challenge_method' => 'plain',
        ]);

        return redirect($url);
    }

    /**
     * Connect to Pinterest Ads
     */
    public function connectPinterest()
    {
        $clientId = config('services.pinterest.client_id');
        $redirectUri = route('marketing.oauth.callback', ['platform' => 'pinterest']);

        if (!$clientId) {
            $this->showComingSoon('Pinterest Ads');
            return;
        }

        $url = "https://www.pinterest.com/oauth/?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'ads:read',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    /**
     * Show coming soon notification
     */
    public function showComingSoon(string $platform = 'This platform')
    {
        Notification::make()
            ->title('Coming Soon')
            ->body("{$platform} integration is coming soon! Check back for updates.")
            ->info()
            ->send();
    }

    /**
     * Disconnect a platform
     */
    public function disconnectPlatform(string $platform)
    {
        try {
            $connection = SocialMediaConnection::where('user_id', auth()->id())
                ->where('platform', $platform)
                ->first();

            if ($connection) {
                $platformName = $connection->platform_name ?? ucfirst($platform);
                $connection->delete();

                Notification::make()
                    ->title('Disconnected Successfully')
                    ->body("{$platformName} has been disconnected from your account.")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Disconnect Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Fetch Data from Connected Platform
    public function fetchFromPlatform(string $platform)
    {
        try {
            $connection = SocialMediaConnection::where('user_id', auth()->id())
                ->where('platform', $platform)
                ->firstOrFail();

            $service = app(SocialMediaSyncService::class);

            // Fetch data from platform
            $fetchedData = $service->fetchDataFromPlatform($connection);

            if ($fetchedData) {
                // Add user_id and ensure required fields are present
                $fetchedData['user_id'] = auth()->id();
                $fetchedData['date'] = $fetchedData['date'] ?? now()->toDateString();
                $fetchedData['platform'] = $fetchedData['platform'] ?? $platform;
                $fetchedData['channel'] = $fetchedData['channel'] ?? $this->mapPlatformToChannel($platform);

                // Create the marketing metric record directly
                $metric = MarketingMetric::create($fetchedData);

                $accountName = $connection->account_name ?? ucfirst($platform);

                Notification::make()
                    ->title('Data Imported Successfully!')
                    ->body("Marketing data from {$accountName} has been imported and saved.")
                    ->success()
                    ->duration(5000)
                    ->send();

                // Redirect to the list page to show the imported data
                return redirect()->route('filament.dashboard.resources.marketing-metrics.index');
            } else {
                throw new \Exception('No data returned from platform');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }

    /**
     * Map platform to channel
     */
    protected function mapPlatformToChannel(string $platform): string
    {
        return match($platform) {
            'facebook', 'instagram' => 'facebook',
            'google', 'google_ads' => 'google',
            'linkedin' => 'linkedin',
            'tiktok' => 'organic',
            'twitter' => 'organic',
            'pinterest' => 'organic',
            default => 'organic',
        };
    }
}
