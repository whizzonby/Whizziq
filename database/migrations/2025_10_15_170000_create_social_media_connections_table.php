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
        Schema::create('social_media_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Platform identification
            $table->string('platform'); // facebook, instagram, google_ads, linkedin, twitter
            $table->string('account_id')->nullable(); // Platform-specific account ID
            $table->string('account_name')->nullable(); // Display name

            // OAuth credentials (encrypted)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Platform-specific data (JSON)
            $table->json('platform_data')->nullable(); // Store additional platform-specific info

            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('pending'); // pending, syncing, success, failed
            $table->text('sync_error')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'platform']);
            $table->unique(['user_id', 'platform', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_media_connections');
    }
};
