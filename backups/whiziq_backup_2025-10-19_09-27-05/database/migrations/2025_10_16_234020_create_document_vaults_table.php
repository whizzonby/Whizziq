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
        Schema::create('document_vaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, docx, xlsx, etc.
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->text('ai_summary')->nullable();
            $table->text('ai_key_points')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'is_favorite']);
            $table->index(['user_id', 'file_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_vaults');
    }
};
