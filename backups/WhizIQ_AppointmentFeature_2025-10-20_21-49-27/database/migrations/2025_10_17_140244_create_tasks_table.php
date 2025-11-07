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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('source', ['manual', 'document', 'meeting', 'voice', 'ai_extracted'])->default('manual');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            // AI Priority Scoring
            $table->integer('ai_priority_score')->nullable(); // 1-100
            $table->text('ai_priority_reasoning')->nullable();

            // Linking to other resources
            $table->foreignId('linked_goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->foreignId('linked_document_id')->nullable()->constrained('document_vaults')->nullOnDelete();

            // Reminders
            $table->boolean('reminder_enabled')->default(false);
            $table->timestamp('reminder_date')->nullable();

            // Additional metadata
            $table->integer('estimated_minutes')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
