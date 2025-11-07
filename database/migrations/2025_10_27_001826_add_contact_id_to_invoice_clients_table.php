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
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('user_id')->constrained('contacts')->nullOnDelete();
            $table->index(['user_id', 'contact_id'], 'idx_invoice_clients_user_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex('idx_invoice_clients_user_contact');
            $table->dropColumn('contact_id');
        });
    }
};
