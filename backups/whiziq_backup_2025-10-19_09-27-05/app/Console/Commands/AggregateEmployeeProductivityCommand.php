<?php

namespace App\Console\Commands;

use App\Services\EmployeeProductivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateEmployeeProductivityCommand extends Command
{
    protected $signature = 'productivity:aggregate {--date= : The date to aggregate (Y-m-d)} {--days=1 : Number of days to aggregate} {--user= : Specific user ID}';

    protected $description = 'Aggregate employee productivity metrics from tasks, attendance, and sentiment data';

    protected EmployeeProductivityService $productivityService;

    public function __construct(EmployeeProductivityService $productivityService)
    {
        parent::__construct();
        $this->productivityService = $productivityService;
    }

    public function handle(): int
    {
        $this->info('Starting productivity aggregation...');

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $days = (int) $this->option('days');
        $userId = $this->option('user');

        $totalProcessed = 0;

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $date->copy()->subDays($i);

            $this->info("Processing {$currentDate->toDateString()}...");

            $count = $this->productivityService->calculateProductivityForAllEmployees(
                $currentDate,
                $userId ? (int) $userId : null
            );

            $totalProcessed += $count;

            $this->info("  ✓ Processed {$count} employees");
        }

        $this->info("✅ Successfully aggregated productivity for {$totalProcessed} employee records");

        return Command::SUCCESS;
    }
}
