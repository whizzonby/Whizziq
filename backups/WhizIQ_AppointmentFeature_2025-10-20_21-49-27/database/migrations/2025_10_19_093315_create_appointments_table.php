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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained()->nullOnDelete();

            // Basic Info
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            // Scheduling - CRITICAL FOR PERFORMANCE
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('timezone', 50)->default('UTC');

            // Status
            $table->enum('status', ['scheduled', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('scheduled');

            // Attendee Info
            $table->string('attendee_name')->nullable();
            $table->string('attendee_email')->nullable();
            $table->string('attendee_phone')->nullable();
            $table->string('attendee_company')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('confirmation_token', 64)->nullable()->unique();

            // Booking source
            $table->enum('booked_via', ['admin', 'public_form'])->default('admin');

            $table->timestamps();
            $table->softDeletes();

            // CRITICAL PERFORMANCE INDEXES
            $table->index(['user_id', 'start_datetime']); // Most common query
            $table->index(['user_id', 'status']);
            $table->index('start_datetime'); // For availability checks
            $table->index(['start_datetime', 'end_datetime']); // For conflict detection
            $table->index('confirmation_token'); // For public bookings
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
