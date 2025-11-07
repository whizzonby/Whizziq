<?php

namespace App\Console\Commands;

use App\Services\BusinessMetricAggregationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateBusinessMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:aggregate
                            {--user= : Specific user ID to aggregate for}
                            {--all : Aggregate for all users}
                            {--days=30 : Number of days to aggregate (default: 30)}
                            {--from= : Start date (Y-m-d format)}
                            {--to= : End date (Y-m-d format)}
                            {--today : Aggregate today only}
                            {--cash-flow : Also generate cash flow history}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate financial data into business metrics for dashboard display';

    protected BusinessMetricAggregationService $aggregationService;

    public function __construct(BusinessMetricAggregationService $aggregationService)
    {
        parent::__construct();
        $this->aggregationService = $aggregationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Business Metrics Aggregation...');
        $this->newLine();

        // Determine date range
        [$startDate, $endDate] = $this->getDateRange();

        $this->info("📅 Date Range: {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->newLine();

        // Determine which users to process
        if ($this->option('all')) {
            return $this->aggregateAllUsers($startDate, $endDate);
        } elseif ($userId = $this->option('user')) {
            return $this->aggregateForUser($userId, $startDate, $endDate);
        } else {
            $this->error('❌ Please specify either --user=ID or --all');
            return self::FAILURE;
        }
    }

    /**
     * Get date range from options
     */
    protected function getDateRange(): array
    {
        if ($this->option('today')) {
            return [Carbon::today(), Carbon::today()];
        }

        $from = $this->option('from');
        $to = $this->option('to');

        if ($from && $to) {
            return [
                Carbon::parse($from),
                Carbon::parse($to),
            ];
        }

        $days = (int) $this->option('days');

        return [
            Carbon::today()->subDays($days),
            Carbon::today(),
        ];
    }

    /**
     * Aggregate for a specific user
     */
    protected function aggregateForUser(int $userId, Carbon $startDate, Carbon $endDate): int
    {
        $this->info("👤 Processing User ID: {$userId}");

        $bar = $this->output->createProgressBar(2);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        // Aggregate business metrics
        $bar->setMessage('Aggregating business metrics...');
        $bar->start();

        $result = $this->aggregationService->aggregateMetrics($userId, $startDate, $endDate);

        $bar->advance();

        // Generate cash flow history if requested
        $cashFlowResult = null;
        if ($this->option('cash-flow')) {
            $bar->setMessage('Generating cash flow history...');

            $months = max(1, $startDate->diffInMonths($endDate) + 1);
            $cashFlowResult = $this->aggregationService->generateCashFlowHistory($userId, $months);

            $bar->advance();
        } else {
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($result, $cashFlowResult);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Aggregate for all users
     */
    protected function aggregateAllUsers(Carbon $startDate, Carbon $endDate): int
    {
        $this->info('👥 Processing ALL users...');
        $this->newLine();

        $result = $this->aggregationService->aggregateAllUsers($startDate, $endDate);

        $this->info("✅ Processed {$result['users_processed']} users");
        $this->newLine();

        // Display summary for each user
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($result['results'] as $userId => $userResult) {
            $totalCreated += $userResult['created'];
            $totalUpdated += $userResult['updated'];
            $totalErrors += count($userResult['errors']);

            if (count($userResult['errors']) > 0) {
                $this->warn("⚠️  User {$userId}: " . count($userResult['errors']) . " errors");
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Metrics Created', $totalCreated],
                ['Total Metrics Updated', $totalUpdated],
                ['Total Errors', $totalErrors],
            ]
        );

        // Generate cash flow history for all users if requested
        if ($this->option('cash-flow')) {
            $this->newLine();
            $this->info('💰 Generating cash flow history for all users...');

            foreach (array_keys($result['results']) as $userId) {
                $months = max(1, $startDate->diffInMonths($endDate) + 1);
                $this->aggregationService->generateCashFlowHistory($userId, $months);
            }

            $this->info('✅ Cash flow history generated');
        }

        return self::SUCCESS;
    }

    /**
     * Display aggregation results
     */
    protected function displayResults(array $result, ?array $cashFlowResult = null): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Business Metrics Created', $result['created']],
                ['Business Metrics Updated', $result['updated']],
                ['Errors', count($result['errors'])],
            ]
        );

        if (count($result['errors']) > 0) {
            $this->newLine();
            $this->warn('⚠️  Errors encountered:');
            foreach (array_slice($result['errors'], 0, 5) as $error) {
                $this->line('  • ' . $error);
            }

            if (count($result['errors']) > 5) {
                $this->line('  • ... and ' . (count($result['errors']) - 5) . ' more');
            }
        }

        if ($cashFlowResult) {
            $this->newLine();
            $this->table(
                ['Cash Flow Metric', 'Count'],
                [
                    ['Records Created', $cashFlowResult['created']],
                    ['Records Updated', $cashFlowResult['updated']],
                ]
            );
        }

        if ($result['success'] && count($result['errors']) === 0) {
            $this->newLine();
            $this->info('✅ Aggregation completed successfully!');
        }
    }
}
