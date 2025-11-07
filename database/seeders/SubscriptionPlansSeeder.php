<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder reads from PLAN_METADATA_CONFIGURATIONS.json and automatically
     * creates products and plans with all the correct metadata and pricing.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Subscription Plans Seeder...');

        // Read the JSON configuration file
        $jsonPath = base_path('PLAN_METADATA_CONFIGURATIONS.json');

        if (!file_exists($jsonPath)) {
            $this->command->error('❌ PLAN_METADATA_CONFIGURATIONS.json not found!');
            return;
        }

        $config = json_decode(file_get_contents($jsonPath), true);

        // Validate JSON is valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON in PLAN_METADATA_CONFIGURATIONS.json: ' . json_last_error_msg());
            return;
        }

        // Validate required metadata keys exist
        $requiredKeys = ['STARTER_PLAN_METADATA', 'PRO_PLAN_METADATA', 'PREMIUM_PLAN_METADATA'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                $this->command->error("❌ Missing metadata key in JSON: {$key}");
                return;
            }
            if (empty($config[$key])) {
                $this->command->warn("⚠️  WARNING: {$key} is empty in JSON file!");
            }
        }

        // Get the monthly interval (most common for SaaS)
        $monthlyInterval = Interval::where('slug', 'month')->first();
        if (!$monthlyInterval) {
            $this->command->error('❌ Monthly interval not found. Run IntervalsSeeder first.');
            return;
        }

        // Get USD currency (or your default currency)
        $usdCurrency = Currency::where('code', 'USD')->first();
        if (!$usdCurrency) {
            $this->command->error('❌ USD currency not found. Run CurrenciesSeeder first.');
            return;
        }

        // Define the plans with their configurations
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for individuals and small teams getting started',
                'price' => 2999, // $29.99
                'metadata_key' => 'STARTER_PLAN_METADATA',
                'features' => [
                    '500 Contacts',
                    '25 Deals',
                    '100 Tasks',
                    '50 Monthly Invoices',
                    '50 Passwords',
                    '50 Appointments/month',
                    '5 Email Templates',
                    '1GB Document Storage',
                    '20 AI Requests/day',
                    'Standard Support',
                ],
                'is_popular' => false,
                'is_default' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For growing businesses that need more power and automation',
                'price' => 3999, // $39.99
                'metadata_key' => 'PRO_PLAN_METADATA',
                'features' => [
                    'Unlimited Contacts & Deals',
                    'Unlimited Tasks & Invoices',
                    '200 Passwords',
                    'Contact Segmentation',
                    'Tax Management',
                    'Goals & OKR Tracking',
                    'Calendar & Zoom Integration',
                    'Import/Export Data',
                    '5GB Document Storage',
                    '75 AI Requests/day',
                    'Priority Support',
                ],
                'is_popular' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Full-featured plan with AI superpowers for power users',
                'price' => 4999, // $49.99
                'metadata_key' => 'PREMIUM_PLAN_METADATA',
                'features' => [
                    'Everything in Pro, plus:',
                    'Unlimited Storage & Passwords',
                    '18 AI-Powered Features',
                    'AI Email Generation',
                    'AI Document Analysis',
                    'AI Task Extraction',
                    'SWOT & Risk Analysis',
                    'Revenue Forecasting',
                    'Tax Optimization AI',
                    'QuickBooks/Xero Integration',
                    '200 AI Requests/day',
                    'Premium Support (4-hour response)',
                ],
                'is_popular' => false,
                'is_default' => false,
            ],
        ];

        // Ensure only Starter is the default product (reset others first)
        Product::where('is_default', true)->update(['is_default' => false]);

        // Create products and plans
        foreach ($plans as $planConfig) {
            $this->command->info("📦 Creating {$planConfig['name']} plan...");

            // Get metadata from config
            $metadata = $config[$planConfig['metadata_key']] ?? [];
            
            // Validate metadata exists
            if (empty($metadata)) {
                $this->command->error("   ❌ ERROR: {$planConfig['name']} has NO metadata! This will cause access control issues!");
                $this->command->warn("   ⚠️  Metadata key '{$planConfig['metadata_key']}' not found in JSON or is empty!");
                continue; // Skip this product
            }

            // Create or update the product
            $product = Product::updateOrCreate(
                ['slug' => $planConfig['slug']],
                [
                    'name' => $planConfig['name'],
                    'description' => $planConfig['description'],
                    'features' => $planConfig['features'],
                    'is_popular' => $planConfig['is_popular'],
                    'is_default' => $planConfig['is_default'],
                    'metadata' => $metadata,
                ]
            );

            $this->command->info("   ✓ Product created/updated: {$product->name}");
            $metadataCount = count($product->metadata ?? []);
            $this->command->info("   ✓ Metadata keys: {$metadataCount}");
            
            // Warn if metadata is empty after save
            if ($metadataCount === 0) {
                $this->command->error("   ❌ CRITICAL: {$product->name} has NO metadata after save! Users will get full access!");
            }

            // Create or update the plan
            $plan = Plan::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'slug' => $planConfig['slug'] . '-monthly',
                ],
                [
                    'name' => $planConfig['name'] . ' - Monthly',
                    'description' => $planConfig['description'],
                    'interval_id' => $monthlyInterval->id,
                    'interval_count' => 1,
                    'has_trial' => true,
                    'trial_interval_id' => $monthlyInterval->id,
                    'trial_interval_count' => 0, // No trial for now, set to 7 or 14 if you want
                    'is_active' => true,
                    'is_visible' => true,
                    'type' => 'flat_rate',
                ]
            );

            $this->command->info("   ✓ Plan created/updated: {$plan->name}");

            // Create or update the plan price
            $planPrice = PlanPrice::updateOrCreate(
                [
                    'plan_id' => $plan->id,
                    'currency_id' => $usdCurrency->id,
                ],
                [
                    'price' => $planConfig['price'], // Price in cents
                ]
            );

            $this->command->info("   ✓ Price set: $" . number_format($planConfig['price'] / 100, 2));
            $this->command->info("   ✅ {$planConfig['name']} plan complete!\n");
        }

        // Display summary
        $this->displaySummary($config);
    }

    /**
     * Display a summary of what was created
     */
    private function displaySummary(array $config): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✨ SUBSCRIPTION PLANS SEEDER COMPLETED! ✨');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('   • Products Created: 3 (Starter, Pro, Premium)');
        $this->command->info('   • Plans Created: 3 (Monthly subscriptions)');
        $this->command->info('   • Metadata Keys per Plan: ' . count($config['STARTER_PLAN_METADATA'] ?? []));
        $this->command->info('');
        $this->command->info('💰 Pricing:');
        $this->command->info('   • Starter: $29.99/month');
        $this->command->info('   • Pro: $39.99/month (Most Popular)');
        $this->command->info('   • Premium: $49.99/month');
        $this->command->info('');
        $this->command->info('🎯 Next Steps:');
        $this->command->info('   1. Configure payment provider settings (Stripe/Paddle/etc.)');
        $this->command->info('   2. Test subscription flow in your admin panel');
        $this->command->info('   3. Verify feature gates work correctly');
        $this->command->info('   4. Update trial period if needed (currently: no trial)');
        $this->command->info('');
        $this->command->info('📝 To modify plans, edit:');
        $this->command->info('   • PLAN_METADATA_CONFIGURATIONS.json (for feature flags)');
        $this->command->info('   • This seeder (for pricing/descriptions)');
        $this->command->info('   • Then run: php artisan db:seed --class=SubscriptionPlansSeeder');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
