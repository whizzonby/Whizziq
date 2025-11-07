<?php

namespace App\Console\Commands;

use App\Models\SocialMediaConnection;
use App\Services\SocialMedia\SocialMediaSyncService;
use Illuminate\Console\Command;

class SyncMarketingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketing:sync
                            {--user= : Sync for a specific user ID}
                            {--platform= : Sync only a specific platform}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync marketing metrics from connected social media accounts';

    /**
     * Execute the console command.
     */
    public function handle(SocialMediaSyncService $syncService): int
    {
        $this->info('Starting marketing data sync...');

        $query = SocialMediaConnection::active();

        // Filter by user if specified
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        // Filter by platform if specified
        if ($platform = $this->option('platform')) {
            $query->where('platform', $platform);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->warn('No active connections found to sync.');
            return self::SUCCESS;
        }

        $this->info("Found {$connections->count()} connection(s) to sync.");

        $successCount = 0;
        $failedCount = 0;

        $progressBar = $this->output->createProgressBar($connections->count());
        $progressBar->start();

        foreach ($connections as $connection) {
            try {
                $this->line("\nSyncing {$connection->platform_name} for user #{$connection->user_id}...");

                $syncService->syncConnection($connection);

                $successCount++;
                $this->info("✓ {$connection->platform_name} synced successfully");
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("✗ {$connection->platform_name} sync failed: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("Sync completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failedCount],
                ['Total', $connections->count()],
            ]
        );

        return self::SUCCESS;
    }
}
