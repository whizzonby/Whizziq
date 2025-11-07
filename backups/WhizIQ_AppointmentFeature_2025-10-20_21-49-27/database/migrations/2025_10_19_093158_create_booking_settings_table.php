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
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Public URL
            $table->string('booking_slug', 100)->unique();
            $table->boolean('is_booking_enabled')->default(true);

            // Branding
            $table->string('display_name')->nullable();
            $table->text('welcome_message')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('brand_color', 7)->default('#3B82F6');

            // Settings
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('require_approval')->default(false);
            $table->integer('min_booking_notice_hours')->default(24);
            $table->integer('max_booking_days_ahead')->default(60);

            // Notifications
            $table->string('notify_email')->nullable();
            $table->string('notify_sms', 20)->nullable();
            $table->integer('send_reminder_hours_before')->default(24);

            $table->timestamps();

            // Indexes
            $table->unique('user_id');
            $table->index('booking_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_settings');
    }
};
