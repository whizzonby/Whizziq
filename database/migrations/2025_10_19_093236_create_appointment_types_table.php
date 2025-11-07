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
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 10, 2)->default(0);

            // Availability
            $table->boolean('is_active')->default(true);
            $table->string('color', 7)->default('#3B82F6');

            // Settings
            $table->integer('buffer_before_minutes')->default(0);
            $table->integer('buffer_after_minutes')->default(0);
            $table->integer('max_per_day')->nullable();

            // Booking form fields
            $table->boolean('require_phone')->default(false);
            $table->boolean('require_company')->default(false);
            $table->json('custom_questions')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};
