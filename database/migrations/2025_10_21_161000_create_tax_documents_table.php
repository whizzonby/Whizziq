<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_period_id')->nullable()->constrained()->onDelete('set null');

            // Document Information
            $table->enum('document_type', [
                'w2', 'w9', '1099_nec', '1099_misc', '1099_int', '1099_div',
                'receipt', 'invoice', 'bank_statement', 'other'
            ]);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, jpg, png, etc.
            $table->bigInteger('file_size'); // bytes

            // OCR and Extracted Data
            $table->json('extracted_data')->nullable(); // OCR extracted data
            $table->boolean('ocr_processed')->default(false);
            $table->timestamp('ocr_processed_at')->nullable();

            // Document Metadata
            $table->integer('tax_year');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_tin')->nullable();

            // Verification Status
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Integration
            $table->boolean('linked_to_expense')->default(false);
            $table->foreignId('expense_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'tax_year']);
            $table->index(['document_type', 'tax_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_documents');
    }
};
