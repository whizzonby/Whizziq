<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'tax_period_id',
        'document_type',
        'document_name',
        'file_path',
        'file_type',
        'file_size',
        'extracted_data',
        'ocr_processed',
        'ocr_processed_at',
        'tax_year',
        'amount',
        'payer_name',
        'payer_tin',
        'verification_status',
        'verification_notes',
        'verified_at',
        'linked_to_expense',
        'expense_id',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'ocr_processed' => 'boolean',
        'ocr_processed_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount' => 'decimal:2',
        'linked_to_expense' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxPeriod(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getFileUrl(): string
    {
        return \Storage::url($this->file_path);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function getDocumentTypeName(): string
    {
        return match($this->document_type) {
            'w2' => 'W-2 Wage Statement',
            'w9' => 'W-9 Request for Taxpayer ID',
            '1099_nec' => '1099-NEC Nonemployee Compensation',
            '1099_misc' => '1099-MISC Miscellaneous Income',
            '1099_int' => '1099-INT Interest Income',
            '1099_div' => '1099-DIV Dividend Income',
            'receipt' => 'Receipt',
            'invoice' => 'Invoice',
            'bank_statement' => 'Bank Statement',
            default => 'Other Document',
        };
    }
}
