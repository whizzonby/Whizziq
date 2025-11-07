<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Console\Command;

class TestPlanLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:test-limits 
                          {--user= : Test specific user ID}
                          {--all : Test all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test plan limits to verify they work correctly';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧪 Testing Plan Limits...');
        $this->newLine();

        $userId = $this->option('user');
        $testAll = $this->option('all');

        if ($testAll) {
            $users = User::where('is_admin', false)->limit(5)->get();
            if ($users->isEmpty()) {
                $this->error('❌ No regular users found!');
                return self::FAILURE;
            }
        } elseif ($userId) {
            $users = collect([User::find($userId)]);
            if (!$users->first()) {
                $this->error("❌ User with ID {$userId} not found!");
                return self::FAILURE;
            }
        } else {
            // Test with first non-admin user or create test scenario
            $user = User::where('is_admin', false)->first();
            if (!$user) {
                $this->error('❌ No regular users found! Create a test user first.');
                return self::FAILURE;
            }
            $users = collect([$user]);
        }

        foreach ($users as $user) {
            $this->testUserLimits($user);
            $this->newLine();
        }

        // Test admin bypass
        $admin = User::where('is_admin', true)->first();
        if ($admin) {
            $this->info('🔐 Testing Admin Bypass...');
            $this->testAdminBypass($admin);
            $this->newLine();
        }

        // Test metadata retrieval
        $this->info('📊 Testing Metadata Retrieval...');
        $this->testMetadataRetrieval();
        $this->newLine();

        $this->info('✅ Testing complete!');
        return self::SUCCESS;
    }

    /**
     * Test limits for a specific user
     */
    private function testUserLimits(User $user): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("👤 Testing User: {$user->name} (ID: {$user->id})");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Get user's metadata
        $metadata = $user->subscriptionProductMetadata();
        
        if (empty($metadata)) {
            $this->warn("   ⚠️  No metadata found - user has no subscription/default product");
            $this->line("   📋 This means limits will be enforced (no access)");
        } else {
            $this->info("   ✅ Metadata found: " . count($metadata) . " keys");
        }

        // Test limits
        $tests = [
            ['model' => Contact::class, 'key' => 'crm_contacts_limit', 'name' => 'Contacts'],
            ['model' => Deal::class, 'key' => 'crm_deals_limit', 'name' => 'Deals'],
            ['model' => Task::class, 'key' => 'tasks_limit', 'name' => 'Tasks'],
            ['model' => Appointment::class, 'key' => 'appointments_limit', 'name' => 'Appointments'],
        ];

        $this->line("   📊 Current Limits:");
        foreach ($tests as $test) {
            $limit = $metadata[$test['key']] ?? 'not set';
            $current = $test['model']::where('user_id', $user->id)->count();
            $canCreate = $user->canCreate($test['model'], $test['key']);
            
            $status = $canCreate ? '✅' : '❌';
            $limitDisplay = $limit === 'unlimited' ? '∞' : $limit;
            
            $this->line("      {$status} {$test['name']}: {$current} / {$limitDisplay} - " . ($canCreate ? 'Can Create' : 'BLOCKED'));
        }

        // Test feature flags
        $this->line("   🎯 Feature Flags:");
        $features = [
            ['key' => 'goals_enabled', 'name' => 'Goals'],
            ['key' => 'crm_segments', 'name' => 'Contact Segments'],
            ['key' => 'tax_enabled', 'name' => 'Tax Management'],
        ];

        foreach ($features as $feature) {
            $hasFeature = $user->hasFeature($feature['key']);
            $value = $metadata[$feature['key']] ?? 'not set';
            $status = $hasFeature ? '✅' : '❌';
            $reason = $user->isAdmin() ? ' (admin bypass)' : '';
            $this->line("      {$status} {$feature['name']}: " . ($hasFeature ? 'Enabled' : 'Disabled') . " (metadata: {$value}){$reason}");
        }
    }

    /**
     * Test admin bypass
     */
    private function testAdminBypass(User $admin): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("👤 Admin: {$admin->name} (ID: {$admin->id})");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $tests = [
            ['model' => Contact::class, 'key' => 'crm_contacts_limit', 'name' => 'Contacts'],
            ['model' => Deal::class, 'key' => 'crm_deals_limit', 'name' => 'Deals'],
        ];

        foreach ($tests as $test) {
            $canCreate = $admin->canCreate($test['model'], $test['key']);
            $hasFeature = $admin->hasFeature('goals_enabled');
            
            if ($canCreate && $hasFeature) {
                $this->info("   ✅ {$test['name']}: Admin bypass working (can create, has features)");
            } else {
                $this->error("   ❌ {$test['name']}: Admin bypass NOT working!");
            }
        }
    }

    /**
     * Test metadata retrieval
     */
    private function testMetadataRetrieval(): void
    {
        $products = Product::all();
        
        if ($products->isEmpty()) {
            $this->error('   ❌ No products found!');
            return;
        }

        $this->line("   📦 Products with Metadata:");
        foreach ($products as $product) {
            $metadata = $product->metadata ?? [];
            $count = count($metadata);
            $status = $count > 0 ? '✅' : '❌';
            
            $this->line("      {$status} {$product->name}: {$count} metadata keys");
            
            if ($count > 0) {
                // Show key limits
                $keyLimits = [
                    'crm_contacts_limit',
                    'crm_deals_limit',
                    'tasks_limit',
                    'appointments_limit',
                ];
                
                foreach ($keyLimits as $key) {
                    if (isset($metadata[$key])) {
                        $this->line("         • {$key}: {$metadata[$key]}");
                    }
                }
            }
        }

        // Check default product
        $defaultProduct = Product::where('is_default', true)->first();
        if ($defaultProduct) {
            $this->info("   ✅ Default Product: {$defaultProduct->name}");
        } else {
            $this->warn("   ⚠️  No default product set!");
        }
    }
}

