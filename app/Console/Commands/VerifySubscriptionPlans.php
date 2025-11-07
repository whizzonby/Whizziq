<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Console\Command;

class VerifySubscriptionPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:verify
                          {--detailed : Show detailed metadata for each plan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify subscription plans are set up correctly with all metadata';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verifying Subscription Plans Setup...');
        $this->newLine();

        $products = Product::with('plans.prices')->get();

        if ($products->isEmpty()) {
            $this->error('❌ No products found! Run: php artisan db:seed --class=SubscriptionPlansSeeder');
            return self::FAILURE;
        }

        $this->info("✅ Found {$products->count()} products");
        $this->newLine();

        $errors = [];
        $warnings = [];

        foreach ($products as $product) {
            $this->displayProductInfo($product, $errors, $warnings);
        }

        $this->displaySummary($products, $errors, $warnings);

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Display product information
     */
    private function displayProductInfo(Product $product, array &$errors, array &$warnings): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📦 Product: {$product->name}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Check metadata
        $metadata = $product->metadata ?? [];
        $metadataCount = count($metadata);

        if ($metadataCount === 0) {
            $errors[] = "{$product->name}: No metadata found";
            $this->error("   ❌ Metadata: MISSING");
        } else {
            $this->info("   ✅ Metadata: {$metadataCount} keys");

            if ($metadataCount < 70) {
                $warnings[] = "{$product->name}: Only {$metadataCount} metadata keys (expected 80+)";
                $this->warn("   ⚠️  Warning: Expected 80+ keys, found {$metadataCount}");
            }

            // Show critical feature flags
            $this->checkCriticalFeatures($product, $metadata, $errors, $warnings);
        }

        // Check features array
        $features = $product->features ?? [];
        if (empty($features)) {
            $warnings[] = "{$product->name}: No feature list found";
            $this->warn("   ⚠️  Features list: Empty");
        } else {
            $this->info("   ✅ Feature list: " . count($features) . " items");
        }

        // Check plans
        if ($product->plans->isEmpty()) {
            $errors[] = "{$product->name}: No plans found";
            $this->error("   ❌ Plans: NONE");
        } else {
            foreach ($product->plans as $plan) {
                $this->displayPlanInfo($plan, $errors, $warnings);
            }
        }

        // Show detailed metadata if requested
        if ($this->option('detailed')) {
            $this->displayDetailedMetadata($metadata);
        }

        $this->newLine();
    }

    /**
     * Check critical feature flags
     */
    private function checkCriticalFeatures(Product $product, array $metadata, array &$errors, array &$warnings): void
    {
        $criticalFeatures = [
            'crm_contacts_limit',
            'crm_deals_limit',
            'finance_invoices_limit',
            'tasks_limit',
            'passwords_limit',
            'ai_daily_limit',
            'ai_email_features',
            'tax_enabled',
        ];

        $missing = [];
        foreach ($criticalFeatures as $feature) {
            if (!isset($metadata[$feature])) {
                $missing[] = $feature;
            }
        }

        if (!empty($missing)) {
            $warnings[] = "{$product->name}: Missing critical features: " . implode(', ', $missing);
            $this->warn("   ⚠️  Missing critical features: " . implode(', ', $missing));
        }
    }

    /**
     * Display plan information
     */
    private function displayPlanInfo(Plan $plan, array &$errors, array &$warnings): void
    {
        $this->line("   📋 Plan: {$plan->name}");
        $this->line("      Slug: {$plan->slug}");
        $this->line("      Active: " . ($plan->is_active ? '✅ Yes' : '❌ No'));
        $this->line("      Visible: " . ($plan->is_visible ? '✅ Yes' : '❌ No'));

        if (!$plan->is_active) {
            $warnings[] = "{$plan->name}: Plan is not active";
        }

        if (!$plan->is_visible) {
            $warnings[] = "{$plan->name}: Plan is not visible";
        }

        // Check prices
        if ($plan->prices->isEmpty()) {
            $errors[] = "{$plan->name}: No prices found";
            $this->error("      ❌ Price: MISSING");
        } else {
            foreach ($plan->prices as $price) {
                $amount = number_format($price->price / 100, 2);
                $this->info("      ✅ Price: ${amount} {$price->currency->code}");
            }
        }
    }

    /**
     * Display detailed metadata
     */
    private function displayDetailedMetadata(array $metadata): void
    {
        $this->newLine();
        $this->line("   📊 Metadata Details:");

        $categories = [
            'CRM' => ['crm_'],
            'Finance' => ['finance_'],
            'Tax' => ['tax_'],
            'Appointments' => ['appointments_'],
            'Email' => ['email_'],
            'Documents' => ['documents_'],
            'Passwords' => ['passwords_'],
            'Tasks & Goals' => ['tasks_', 'goals_'],
            'Analytics' => ['analytics_'],
            'AI Features' => ['ai_'],
            'Automation' => ['automation_'],
            'Integrations' => ['integrations_'],
            'Other' => ['support_'],
        ];

        foreach ($categories as $category => $prefixes) {
            $categoryData = array_filter($metadata, function ($key) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($key, $prefix)) {
                        return true;
                    }
                }
                return false;
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($categoryData)) {
                $this->line("      {$category}: " . count($categoryData) . " keys");
            }
        }
    }

    /**
     * Display summary
     */
    private function displaySummary($products, array $errors, array $warnings): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 VERIFICATION SUMMARY");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        $totalPlans = $products->sum(fn($p) => $p->plans->count());
        $totalPrices = $products->sum(fn($p) => $p->plans->sum(fn($plan) => $plan->prices->count()));

        $this->info("✅ Products: {$products->count()}");
        $this->info("✅ Plans: {$totalPlans}");
        $this->info("✅ Prices: {$totalPrices}");
        $this->newLine();

        if (!empty($errors)) {
            $this->error("❌ Errors Found: " . count($errors));
            foreach ($errors as $error) {
                $this->error("   • {$error}");
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn("⚠️  Warnings: " . count($warnings));
            foreach ($warnings as $warning) {
                $this->warn("   • {$warning}");
            }
            $this->newLine();
        }

        if (empty($errors) && empty($warnings)) {
            $this->info("🎉 All checks passed! Your subscription plans are properly configured.");
        } elseif (empty($errors)) {
            $this->info("✅ No critical errors, but please review warnings above.");
        } else {
            $this->error("❌ Critical errors found. Please fix them and run seeder again:");
            $this->line("   php artisan db:seed --class=SubscriptionPlansSeeder");
        }

        $this->newLine();
        $this->line("💡 Tip: Run with --detailed flag to see all metadata categories");
        $this->line("   php artisan subscription:verify --detailed");
    }
}
