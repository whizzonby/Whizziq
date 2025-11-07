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
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();
            
            // Address
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country_code')->nullable();
            
            // Location for Maps
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Contact Info
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            // Additional Information
            $table->text('parking_info')->nullable();
            $table->text('public_transport_info')->nullable();
            $table->text('access_instructions')->nullable();
            $table->text('directions')->nullable();
            
            // Capacity & Status
            $table->integer('capacity')->nullable()->comment('Max simultaneous appointments at this venue');
            $table->boolean('is_active')->default(true);
            $table->string('image_url')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
