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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Basic Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();

            // Contact Type & Status
            $table->enum('type', ['client', 'lead', 'partner', 'investor', 'vendor', 'other'])->default('lead');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->enum('priority', ['low', 'medium', 'high', 'vip'])->default('medium');

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->default('USA');

            // Social & Web
            $table->string('website')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_handle')->nullable();

            // Relationship Tracking
            $table->date('last_contact_date')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->enum('relationship_strength', ['cold', 'warm', 'hot'])->default('warm');

            // Business Value
            $table->decimal('lifetime_value', 15, 2)->default(0);
            $table->integer('deals_count')->default(0);
            $table->integer('interactions_count')->default(0);

            // Tags & Notes
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();

            // Source
            $table->string('source')->nullable(); // referral, website, event, cold_outreach, etc.

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'next_follow_up_date']);
            $table->index(['user_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
