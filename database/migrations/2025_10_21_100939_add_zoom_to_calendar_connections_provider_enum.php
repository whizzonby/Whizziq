<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to alter the enum column to add 'zoom'
        DB::statement("ALTER TABLE calendar_connections MODIFY COLUMN provider ENUM('google_calendar', 'outlook', 'apple_calendar', 'zoom') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'zoom' from the enum (only if no rows use it)
        DB::statement("ALTER TABLE calendar_connections MODIFY COLUMN provider ENUM('google_calendar', 'outlook', 'apple_calendar') NOT NULL");
    }
};
