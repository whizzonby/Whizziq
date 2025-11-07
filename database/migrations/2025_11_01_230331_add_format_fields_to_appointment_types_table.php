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
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->enum('appointment_format', ['online', 'in_person', 'hybrid', 'phone'])->nullable()->after('sort_order');
            $table->foreignId('default_venue_id')->nullable()->after('appointment_format')->constrained('venues')->nullOnDelete();
            $table->boolean('requires_location')->default(false)->after('default_venue_id');
            $table->json('allowed_venues')->nullable()->after('requires_location')->comment('Array of venue IDs that can be used for this appointment type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->dropForeign(['default_venue_id']);
            $table->dropColumn(['appointment_format', 'default_venue_id', 'requires_location', 'allowed_venues']);
        });
    }
};
