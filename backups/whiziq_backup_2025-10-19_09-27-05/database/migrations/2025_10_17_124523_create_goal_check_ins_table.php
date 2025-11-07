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
        Schema::create('goal_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->integer('progress_update')->nullable(); // Progress percentage at time of check-in
            $table->string('sentiment')->nullable(); // positive, neutral, negative
            $table->json('key_result_updates')->nullable(); // Store updates to key results
            $table->text('blockers')->nullable();
            $table->text('next_steps')->nullable();
            $table->timestamps();

            $table->index(['goal_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_check_ins');
    }
};
