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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_campaign_id')->nullable()->constrained()->nullOnDelete();

            // Email Details
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->longText('body');

            // Sending Info
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();

            // Tracking (for webhook integration later)
            $table->string('message_id')->nullable()->unique(); // Provider message ID
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->json('tracking_data')->nullable(); // Additional tracking info

            // Metadata
            $table->string('email_type')->default('manual'); // manual, campaign, automated
            $table->json('metadata')->nullable(); // Custom data

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'contact_id']);
            $table->index(['user_id', 'status']);
            $table->index(['email_campaign_id']);
            $table->index(['sent_at']);
            $table->index(['recipient_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
