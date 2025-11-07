<?php

namespace App\Jobs;

use App\Models\CalendarConnection;
use App\Services\CalendarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCalendarEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, and 5 minutes

    protected ?CalendarConnection $calendarConnection;
    protected bool $fullSync;

    /**
     * Create a new job instance
     */
    public function __construct(?CalendarConnection $calendarConnection = null, bool $fullSync = false)
    {
        $this->calendarConnection = $calendarConnection;
        $this->fullSync = $fullSync;
    }

    /**
     * Execute the job
     */
    public function handle(CalendarSyncService $syncService): void
    {
        try {
            if ($this->calendarConnection) {
                // Sync a specific connection
                $this->syncSingleConnection($syncService);
            } else {
                // Sync all connections that need syncing
                $this->syncAllConnections($syncService);
            }
        } catch (\Exception $e) {
            Log::error('Calendar sync job failed', [
                'connection_id' => $this->calendarConnection?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Sync a single calendar connection
     */
    protected function syncSingleConnection(CalendarSyncService $syncService): void
    {
        if (!$this->calendarConnection) {
            return;
        }

        Log::info('Starting calendar sync for connection', [
            'connection_id' => $this->calendarConnection->id,
            'provider' => $this->calendarConnection->provider,
            'user_id' => $this->calendarConnection->user_id,
            'full_sync' => $this->fullSync,
        ]);

        $eventCount = $syncService->syncConnection($this->calendarConnection, $this->fullSync);

        Log::info('Completed calendar sync for connection', [
            'connection_id' => $this->calendarConnection->id,
            'events_fetched' => $eventCount,
        ]);
    }

    /**
     * Sync all pending connections
     */
    protected function syncAllConnections(CalendarSyncService $syncService): void
    {
        Log::info('Starting batch calendar sync for all pending connections');

        $results = $syncService->syncAllPendingConnections();

        Log::info('Completed batch calendar sync', $results);
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Calendar sync job failed permanently', [
            'connection_id' => $this->calendarConnection?->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionally notify the user or admin about the failure
        if ($this->calendarConnection) {
            // Could send a notification here
            // Notification::send($this->calendarConnection->user, new CalendarSyncFailedNotification($this->calendarConnection));
        }
    }

    /**
     * Get the tags for the job (for monitoring)
     */
    public function tags(): array
    {
        $tags = ['calendar-sync'];

        if ($this->calendarConnection) {
            $tags[] = "connection:{$this->calendarConnection->id}";
            $tags[] = "provider:{$this->calendarConnection->provider}";
            $tags[] = "user:{$this->calendarConnection->user_id}";
        } else {
            $tags[] = 'batch-sync';
        }

        if ($this->fullSync) {
            $tags[] = 'full-sync';
        }

        return $tags;
    }
}
