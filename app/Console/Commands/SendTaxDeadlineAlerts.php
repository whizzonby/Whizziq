<?php

namespace App\Console\Commands;

use App\Services\DeadlineAlertService;
use Illuminate\Console\Command;

class SendTaxDeadlineAlerts extends Command
{
    protected $signature = 'tax:send-deadline-alerts';
    
    protected $description = 'Send tax deadline alerts to users';
    
    public function handle(): int
    {
        $this->info('Sending tax deadline alerts...');
        
        $deadlineService = app(DeadlineAlertService::class);
        $deadlineService->sendDeadlineAlerts();
        
        $this->info('Tax deadline alerts sent successfully!');
        
        return Command::SUCCESS;
    }
}
