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
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Provider information
            $table->enum('provider', ['google_calendar', 'outlook', 'apple_calendar', 'zoom'])->index();
            $table->string('provider_user_id')->nullable();
            $table->string('provider_email')->nullable();

            // OAuth tokens (encrypted)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Calendar settings
            $table->boolean('is_primary')->default(false);
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('last_synced_at')->nullable();

            // Provider-specific data
            $table->string('calendar_id')->nullable(); // Provider's calendar ID
            $table->string('calendar_timezone')->nullable();
            $table->text('sync_token')->nullable(); // For incremental sync

            // Metadata
            $table->string('calendar_name')->nullable();
            $table->json('scopes')->nullable(); // Granted OAuth scopes

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'provider']);
            $table->index(['user_id', 'is_primary']);
            $table->index(['sync_enabled', 'last_synced_at']);
        });

        // Add external_calendar_event_id to appointments for synced events
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('external_calendar_event_id')->nullable()->after('meeting_password');
            $table->foreignId('calendar_connection_id')->nullable()->after('external_calendar_event_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['calendar_connection_id']);
            $table->dropColumn(['external_calendar_event_id', 'calendar_connection_id']);
        });

        Schema::dropIfExists('calendar_connections');
    }
};
