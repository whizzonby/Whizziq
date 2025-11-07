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
        Schema::create('key_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('metric_type'); // number, currency, percentage, boolean
            $table->decimal('start_value', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('target_value', 15, 2);
            $table->string('unit')->nullable(); // $, %, customers, users, etc.
            $table->string('status')->default('not_started'); // not_started, on_track, at_risk, off_track, completed
            $table->integer('progress_percentage')->default(0); // 0-100
            $table->timestamps();
            $table->softDeletes();

            $table->index(['goal_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_results');
    }
};
