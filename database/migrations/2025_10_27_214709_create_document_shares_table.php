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
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_vault_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users');
            $table->foreignId('shared_with_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission_level', ['view', 'download', 'edit'])->default('view');
            $table->boolean('can_download')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_reshare')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['document_vault_id', 'shared_with_user_id']);
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
