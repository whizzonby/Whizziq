<?php

namespace App\Console\Commands;

use App\Services\AutoCategorizationService;
use App\Models\User;
use Illuminate\Console\Command;

class AutoCategorizeExpenses extends Command
{
    protected $signature = 'tax:auto-categorize {--user= : Specific user ID to process}';
    
    protected $description = 'Auto-categorize expenses for tax purposes';
    
    public function handle(): int
    {
        $this->info('Starting auto-categorization...');
        
        $autoCategorizationService = app(AutoCategorizationService::class);
        
        if ($userId = $this->option('user')) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }
            
            $this->processUser($user, $autoCategorizationService);
        } else {
            $users = User::whereHas('expenses')->get();
            
            $this->info("Processing {$users->count()} users...");
            
            foreach ($users as $user) {
                $this->processUser($user, $autoCategorizationService);
            }
        }
        
        $this->info('Auto-categorization completed!');
        
        return Command::SUCCESS;
    }
    
    protected function processUser(User $user, AutoCategorizationService $service): void
    {
        $this->info("Processing user: {$user->name} (ID: {$user->id})");
        
        $results = $service->autoCategorizeExpenses($user);
        
        $this->info("  - Processed: {$results['processed']} expenses");
        $this->info("  - Categorized: {$results['categorized']} expenses");
        $this->info("  - Uncategorized: {$results['uncategorized']} expenses");
        
        if (!empty($results['categories'])) {
            $this->info("  - Categories used:");
            foreach ($results['categories'] as $category => $count) {
                $this->info("    - {$category}: {$count} expenses");
            }
        }
    }
}
