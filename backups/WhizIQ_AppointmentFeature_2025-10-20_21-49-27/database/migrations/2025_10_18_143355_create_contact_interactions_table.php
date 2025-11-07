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
        Schema::create('contact_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();

            // Interaction Details
            $table->enum('type', [
                'call',
                'email',
                'meeting',
                'note',
                'task',
                'demo',
                'proposal',
                'contract',
                'other'
            ])->default('note');

            $table->string('subject')->nullable();
            $table->text('description');

            // Timing
            $table->dateTime('interaction_date');
            $table->integer('duration_minutes')->nullable(); // For calls/meetings

            // Outcome
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'follow_up_needed'])->nullable();

            // Attachments
            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'contact_id']);
            $table->index(['user_id', 'deal_id']);
            $table->index(['user_id', 'interaction_date']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_interactions');
    }
};
