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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature'); // 'email_generation', 'document_analysis', 'business_insights', etc.
            $table->string('action')->nullable(); // 'generate', 'improve', 'analyze', etc.
            $table->integer('tokens_used')->default(0);
            $table->integer('cost_cents')->default(0); // Cost in cents
            $table->text('prompt_summary')->nullable(); // First 200 chars of prompt
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('requested_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'feature']);
            $table->index('requested_at');
        });

        // Create ai_usage_limits table for storing daily limits per plan
        Schema::create('ai_usage_limits', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name'); // 'basic', 'pro', 'premium'
            $table->integer('daily_limit');
            $table->integer('daily_document_analysis_limit')->default(999); // 999 = unlimited
            $table->boolean('has_task_extraction')->default(false);
            $table->boolean('has_auto_categorization')->default(false);
            $table->boolean('has_marketing_insights')->default(false);
            $table->boolean('has_advanced_features')->default(false);
            $table->timestamps();

            $table->unique('plan_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_limits');
        Schema::dropIfExists('ai_usage_logs');
    }
};
