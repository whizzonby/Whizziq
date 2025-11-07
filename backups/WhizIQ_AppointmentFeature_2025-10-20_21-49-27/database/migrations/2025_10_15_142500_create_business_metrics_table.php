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
        Schema::create('business_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->decimal('expenses', 15, 2)->default(0);
            $table->decimal('cash_flow', 15, 2)->default(0);
            $table->decimal('revenue_change_percentage', 8, 2)->nullable();
            $table->decimal('profit_change_percentage', 8, 2)->nullable();
            $table->decimal('expenses_change_percentage', 8, 2)->nullable();
            $table->decimal('cash_flow_change_percentage', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_metrics');
    }
};
