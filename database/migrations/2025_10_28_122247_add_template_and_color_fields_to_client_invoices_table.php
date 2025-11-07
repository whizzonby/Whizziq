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
        Schema::table('client_invoices', function (Blueprint $table) {
            $table->string('template')->default('modern')->after('footer');
            $table->string('primary_color')->default('#3b82f6')->after('template');
            $table->string('accent_color')->default('#60a5fa')->after('primary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_invoices', function (Blueprint $table) {
            $table->dropColumn(['template', 'primary_color', 'accent_color']);
        });
    }
};
