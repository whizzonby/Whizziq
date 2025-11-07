<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('tax_category_id')->nullable()->after('category')->constrained('tax_categories')->nullOnDelete();
            $table->boolean('is_tax_deductible')->default(true)->after('tax_category_id');
            $table->decimal('deductible_amount', 15, 2)->nullable()->after('is_tax_deductible');
            $table->text('tax_notes')->nullable()->after('deductible_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['tax_category_id']);
            $table->dropColumn(['tax_category_id', 'is_tax_deductible', 'deductible_amount', 'tax_notes']);
        });
    }
};
