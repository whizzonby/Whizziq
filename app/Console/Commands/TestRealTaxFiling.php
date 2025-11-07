<?php

namespace App\Console\Commands;

use App\Services\RealTaxFilingService;
use App\Models\User;
use Illuminate\Console\Command;

class TestRealTaxFiling extends Command
{
    protected $signature = 'tax:test-real-filing {user_id}';
    
    protected $description = 'Test real tax filing system with actual form generation';
    
    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return Command::FAILURE;
        }
        
        $this->info("Testing real tax filing for user: {$user->name} (ID: {$user->id})");
        
        $realTaxFilingService = app(RealTaxFilingService::class);
        
        try {
            $result = $realTaxFilingService->fileTaxes($user, now()->year);
            
            if ($result['success']) {
                $this->info("✅ Tax filing successful!");
                $this->info("Confirmation Number: " . $result['confirmation_number']);
                $this->info("Filing Date: " . $result['filing_date']);
                $this->info("Forms Filed: " . implode(', ', $result['forms_filed']));
                
                if (!empty($result['pdf_documents'])) {
                    $this->info("PDF Documents Generated:");
                    foreach ($result['pdf_documents'] as $formType => $pdfPath) {
                        $this->info("  - {$formType}: {$pdfPath}");
                    }
                }
                
                if (!empty($result['submission_details'])) {
                    $this->info("Submission Details:");
                    $this->info("  - IRS Status: " . ($result['submission_details']['irs']['status'] ?? 'Unknown'));
                    $this->info("  - State Status: " . ($result['submission_details']['state']['status'] ?? 'Unknown'));
                }
                
            } else {
                $this->error("❌ Tax filing failed!");
                $this->error("Error: " . $result['message']);
                
                if (!empty($result['error'])) {
                    $this->error("Details: " . $result['error']);
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Exception occurred: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
