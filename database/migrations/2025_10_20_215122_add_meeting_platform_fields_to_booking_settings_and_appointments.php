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
        // Add meeting platform fields to booking_settings
        Schema::table('booking_settings', function (Blueprint $table) {
            $table->enum('meeting_platform', ['none', 'zoom', 'google_meet'])->default('none')->after('send_reminder_hours_before');
            $table->text('zoom_client_id')->nullable()->after('meeting_platform');
            $table->text('zoom_client_secret')->nullable()->after('zoom_client_id');
            $table->text('zoom_access_token')->nullable()->after('zoom_client_secret');
            $table->text('zoom_refresh_token')->nullable()->after('zoom_access_token');
            $table->timestamp('zoom_token_expires_at')->nullable()->after('zoom_refresh_token');
            $table->boolean('google_meet_enabled')->default(false)->after('zoom_token_expires_at');
        });

        // Add meeting details to appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('meeting_platform')->nullable()->after('booked_via');
            $table->text('meeting_url')->nullable()->after('meeting_platform');
            $table->string('meeting_id')->nullable()->after('meeting_url');
            $table->text('meeting_password')->nullable()->after('meeting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_settings', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_platform',
                'zoom_client_id',
                'zoom_client_secret',
                'zoom_access_token',
                'zoom_refresh_token',
                'zoom_token_expires_at',
                'google_meet_enabled',
            ]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_platform',
                'meeting_url',
                'meeting_id',
                'meeting_password',
            ]);
        });
    }
};
