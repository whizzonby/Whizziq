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
        Schema::table('document_vaults', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->index()->after('file_size');
            $table->longText('extracted_text')->nullable()->after('ai_analysis');
            $table->timestamp('expires_at')->nullable()->after('last_accessed_at');
            $table->string('retention_policy')->nullable()->after('expires_at');
            $table->unsignedInteger('version_number')->default(1)->after('retention_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_vaults', function (Blueprint $table) {
            $table->dropColumn(['file_hash', 'extracted_text', 'expires_at', 'retention_policy', 'version_number']);
        });
    }
};
