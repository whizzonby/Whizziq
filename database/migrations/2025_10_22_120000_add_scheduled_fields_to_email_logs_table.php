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
        // First, update the enum to include 'scheduled'
        DB::statement("ALTER TABLE email_logs MODIFY COLUMN status ENUM('pending', 'scheduled', 'sent', 'failed', 'bounced') DEFAULT 'pending'");

        // Add scheduled_at column if it doesn't exist
        Schema::table('email_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('email_logs', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back
        DB::statement("ALTER TABLE email_logs MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending'");

        // Drop scheduled_at column
        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }
        });
    }
};
