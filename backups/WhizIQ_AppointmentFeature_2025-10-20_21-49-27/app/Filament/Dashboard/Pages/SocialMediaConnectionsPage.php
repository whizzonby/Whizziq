<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\SocialMediaConnection;
use App\Services\SocialMedia\SocialMediaSyncService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class SocialMediaConnectionsPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Social Media Connections';

    protected static ?string $title = 'Connect Your Social Media Accounts';

    protected static UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.dashboard.pages.social-media-connections-page';

    public function getConnections()
    {
        return SocialMediaConnection::where('user_id', auth()->id())
            ->orderBy('platform')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAll')
                ->label('Sync All Accounts')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    try {
                        $service = app(SocialMediaSyncService::class);
                        $results = $service->syncAllForUser(auth()->id());

                        if ($results['success'] > 0) {
                            Notification::make()
                                ->title('Sync Completed')
                                ->body("{$results['success']} account(s) synced successfully.")
                                ->success()
                                ->send();
                        }

                        if ($results['failed'] > 0) {
                            Notification::make()
                                ->title('Sync Errors')
                                ->body("{$results['failed']} account(s) failed to sync.")
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function syncConnection($connectionId)
    {
        try {
            $connection = SocialMediaConnection::where('user_id', auth()->id())
                ->findOrFail($connectionId);

            $service = app(SocialMediaSyncService::class);
            $service->syncConnection($connection);

            Notification::make()
                ->title('Sync Successful')
                ->body("{$connection->platform_name} account synced successfully.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Sync Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function toggleConnection($connectionId)
    {
        try {
            $connection = SocialMediaConnection::where('user_id', auth()->id())
                ->findOrFail($connectionId);

            $connection->update([
                'is_active' => !$connection->is_active,
            ]);

            $status = $connection->is_active ? 'activated' : 'deactivated';

            Notification::make()
                ->title('Connection Updated')
                ->body("{$connection->platform_name} connection {$status}.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Update Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteConnection($connectionId)
    {
        try {
            $connection = SocialMediaConnection::where('user_id', auth()->id())
                ->findOrFail($connectionId);

            $platformName = $connection->platform_name;
            $connection->delete();

            Notification::make()
                ->title('Connection Removed')
                ->body("{$platformName} connection has been removed.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Delete Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function connectFacebook()
    {
        // Redirect to Facebook OAuth
        $clientId = config('services.facebook.client_id');
        $redirectUri = route('social-media.callback', ['platform' => 'facebook']);
        $scopes = 'pages_read_engagement,pages_show_list,read_insights';

        $url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
        ]);

        return redirect($url);
    }

    public function connectGoogle()
    {
        // Redirect to Google OAuth
        $clientId = config('services.google.client_id');
        $redirectUri = route('social-media.callback', ['platform' => 'google']);
        $scopes = 'https://www.googleapis.com/auth/adwords';

        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect($url);
    }

    public function connectLinkedIn()
    {
        // Redirect to LinkedIn OAuth
        $clientId = config('services.linkedin-openid.client_id');
        $redirectUri = route('social-media.callback', ['platform' => 'linkedin']);
        $scopes = 'r_ads,r_ads_reporting,rw_ads';

        $url = "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
        ]);

        return redirect($url);
    }
}
