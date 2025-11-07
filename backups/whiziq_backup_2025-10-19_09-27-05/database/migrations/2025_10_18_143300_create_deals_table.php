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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            // Deal Information
            $table->string('title');
            $table->text('description')->nullable();

            // Pipeline Stage
            $table->enum('stage', [
                'lead',           // Initial contact/lead
                'qualified',      // Qualified opportunity
                'proposal',       // Proposal sent
                'negotiation',    // In negotiation
                'won',           // Deal closed won
                'lost'           // Deal closed lost
            ])->default('lead');

            // Financial
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->integer('probability')->default(50); // Win probability percentage

            // Dates
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();

            // Source & Tracking
            $table->string('source')->nullable(); // Where did this deal come from?
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Loss reason (if lost)
            $table->string('loss_reason')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'stage']);
            $table->index(['user_id', 'contact_id']);
            $table->index(['user_id', 'expected_close_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
