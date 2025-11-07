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
        Schema::table('appointments', function (Blueprint $table) {
            // Recurring appointment fields
            $table->boolean('is_recurring')->default(false)->after('calendar_synced_at');
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'custom'])->nullable()->after('is_recurring');
            $table->integer('recurrence_interval')->default(1)->after('recurrence_type'); // Every X days/weeks/months
            $table->json('recurrence_days')->nullable()->after('recurrence_interval'); // For weekly: [1,3,5] = Mon, Wed, Fri
            $table->date('recurrence_end_date')->nullable()->after('recurrence_days');
            $table->integer('recurrence_count')->nullable()->after('recurrence_end_date'); // Number of occurrences
            $table->foreignId('recurring_parent_id')->nullable()->after('recurrence_count')->constrained('appointments')->onDelete('cascade');

            $table->index(['is_recurring', 'recurring_parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['recurring_parent_id']);
            $table->dropColumn([
                'is_recurring',
                'recurrence_type',
                'recurrence_interval',
                'recurrence_days',
                'recurrence_end_date',
                'recurrence_count',
                'recurring_parent_id'
            ]);
        });
    }
};
