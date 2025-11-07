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
        Schema::create('revenue_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('source'); // 'online_sales', 'custom_orders', 'subscriptions', etc.
            $table->decimal('amount', 15, 2);
            $table->decimal('percentage', 5, 2)->nullable(); // Percentage of total revenue
            $table->timestamps();

            $table->index(['user_id', 'date', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_sources');
    }
};
