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
        Schema::table('password_vaults', function (Blueprint $table) {
            $table->timestamp('password_last_changed_at')->nullable()->after('encrypted_password');
            $table->boolean('is_compromised')->default(false)->after('is_favorite');
            $table->timestamp('compromised_at')->nullable()->after('is_compromised');
            $table->boolean('needs_update')->default(false)->after('compromised_at');
            $table->text('health_notes')->nullable()->after('needs_update');

            $table->index('password_last_changed_at');
            $table->index('is_compromised');
            $table->index('needs_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_vaults', function (Blueprint $table) {
            $table->dropIndex(['password_last_changed_at']);
            $table->dropIndex(['is_compromised']);
            $table->dropIndex(['needs_update']);

            $table->dropColumn([
                'password_last_changed_at',
                'is_compromised',
                'compromised_at',
                'needs_update',
                'health_notes',
            ]);
        });
    }
};
