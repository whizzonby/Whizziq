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
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('risk_score')->default(0); // 0-100
            $table->string('risk_level'); // 'low', 'moderate', 'high', 'critical'
            $table->decimal('loan_worthiness', 8, 2)->default(0); // 0-100
            $table->string('loan_worthiness_level'); // 'poor', 'fair', 'good', 'excellent'
            $table->json('risk_factors')->nullable(); // Array of risk descriptions
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
