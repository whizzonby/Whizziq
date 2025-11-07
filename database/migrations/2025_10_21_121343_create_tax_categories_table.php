<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Office Supplies"
            $table->string('slug')->unique(); // e.g., "office_supplies"
            $table->text('description')->nullable();
            $table->decimal('deduction_percentage', 5, 2)->default(100.00); // 100% = fully deductible, 50% = meals
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed with common categories
        DB::table('tax_categories')->insert([
            ['name' => 'Advertising & Marketing', 'slug' => 'advertising', 'description' => 'Ads, promotions, marketing expenses', 'deduction_percentage' => 100.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Office Supplies', 'slug' => 'office_supplies', 'description' => 'Pens, paper, desk supplies', 'deduction_percentage' => 100.00, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Software & Subscriptions', 'slug' => 'software', 'description' => 'SaaS tools, software licenses', 'deduction_percentage' => 100.00, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meals & Entertainment', 'slug' => 'meals', 'description' => 'Business meals (50% deductible)', 'deduction_percentage' => 50.00, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Business travel expenses', 'deduction_percentage' => 100.00, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Vehicle & Transportation', 'slug' => 'vehicle', 'description' => 'Vehicle expenses, mileage', 'deduction_percentage' => 100.00, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rent & Utilities', 'slug' => 'rent_utilities', 'description' => 'Office rent, utilities', 'deduction_percentage' => 100.00, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Professional Services', 'slug' => 'professional_services', 'description' => 'Legal, accounting, consulting', 'deduction_percentage' => 100.00, 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Insurance', 'slug' => 'insurance', 'description' => 'Business insurance premiums', 'deduction_percentage' => 100.00, 'sort_order' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Salaries & Wages', 'slug' => 'salaries', 'description' => 'Employee salaries, wages', 'deduction_percentage' => 100.00, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Contract Labor', 'slug' => 'contract_labor', 'description' => 'Freelancers, contractors', 'deduction_percentage' => 100.00, 'sort_order' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Education & Training', 'slug' => 'education', 'description' => 'Courses, books, training', 'deduction_percentage' => 100.00, 'sort_order' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bank Fees & Interest', 'slug' => 'bank_fees', 'description' => 'Bank charges, loan interest', 'deduction_percentage' => 100.00, 'sort_order' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Depreciation', 'slug' => 'depreciation', 'description' => 'Asset depreciation', 'deduction_percentage' => 100.00, 'sort_order' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other Business Expenses', 'slug' => 'other', 'description' => 'Miscellaneous deductible expenses', 'deduction_percentage' => 100.00, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_categories');
    }
};
