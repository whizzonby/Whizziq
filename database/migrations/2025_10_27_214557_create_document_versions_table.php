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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_vault_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('version_number');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 64);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->text('change_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['document_vault_id', 'version_number']);
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
