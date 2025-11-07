<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\SocialMediaConnection;
use App\Services\SocialMedia\SocialMediaSyncService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class MarketingDataSyncWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.dashboard.widgets.marketing-data-sync-widget';

    protected int | string | array $columnSpan = 'full';

    public function getSyncStatus()
    {
        $connections = SocialMediaConnection::where('user_id', auth()->id())
            ->active()
            ->get();

        return [
            'total' => $connections->count(),
            'syncing' => $connections->where('sync_status', 'syncing')->count(),
            'success' => $connections->where('sync_status', 'success')->count(),
            'failed' => $connections->where('sync_status', 'failed')->count(),
            'last_sync' => $connections->max('last_synced_at'),
            'connections' => $connections,
        ];
    }

    public function syncAll()
    {
        try {
            $service = app(SocialMediaSyncService::class);
            $results = $service->syncAllForUser(auth()->id());

            if ($results['success'] > 0) {
                Notification::make()
                    ->title('Sync Completed')
                    ->body("{$results['success']} account(s) synced successfully. Check your analytics dashboard for updated data.")
                    ->success()
                    ->send();
            }

            if ($results['failed'] > 0) {
                $errorMessages = collect($results['errors'])
                    ->pluck('platform')
                    ->join(', ');

                Notification::make()
                    ->title('Some Syncs Failed')
                    ->body("Failed platforms: {$errorMessages}. Please check your connections.")
                    ->warning()
                    ->send();
            }

            if ($results['success'] == 0 && $results['failed'] == 0) {
                Notification::make()
                    ->title('No Connections')
                    ->body('Please connect your social media accounts first.')
                    ->info()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Sync Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
