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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // quarterly, annual, monthly
            $table->string('category')->nullable(); // revenue, customers, product, team, operational
            $table->date('start_date');
            $table->date('target_date');
            $table->string('status')->default('not_started'); // not_started, in_progress, on_track, at_risk, off_track, completed, abandoned
            $table->integer('progress_percentage')->default(0); // 0-100
            $table->text('ai_suggestions')->nullable();
            $table->timestamp('last_check_in_at')->nullable();
            $table->integer('check_in_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'target_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
