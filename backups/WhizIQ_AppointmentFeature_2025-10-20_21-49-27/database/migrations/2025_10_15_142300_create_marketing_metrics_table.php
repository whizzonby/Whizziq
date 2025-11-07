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
        Schema::create('marketing_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('platform'); // 'facebook', 'instagram', 'linkedin', 'twitter', etc.
            $table->integer('followers')->default(0);
            $table->integer('engagement')->default(0);
            $table->decimal('reach', 15, 0)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'date', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_metrics');
    }
};
