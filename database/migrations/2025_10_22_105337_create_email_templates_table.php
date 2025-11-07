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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Template Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->longText('body'); // HTML content
            $table->enum('category', ['follow_up', 'welcome', 'appointment_reminder', 'marketing', 'other'])->default('other');

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Usage tracking
            $table->integer('times_used')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
