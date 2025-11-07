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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();

            // Campaign Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->longText('body'); // Can be from template or custom

            // Recipients
            $table->enum('recipient_type', ['all_contacts', 'filtered', 'individual', 'custom_list'])->default('individual');
            $table->json('recipient_filters')->nullable(); // Store filter criteria
            $table->json('recipient_ids')->nullable(); // Specific contact IDs

            // Scheduling
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Statistics
            $table->integer('total_recipients')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_failed')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);

            // Settings
            $table->json('attachments')->nullable(); // File paths for attachments
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
