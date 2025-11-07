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
            $table->foreignId('venue_id')->nullable()->after('appointment_type_id')->constrained()->nullOnDelete();
            $table->enum('appointment_format', ['online', 'in_person', 'hybrid', 'phone'])->nullable()->after('venue_id');
            $table->string('room_name')->nullable()->after('appointment_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropColumn(['venue_id', 'appointment_format', 'room_name']);
        });
    }
};
