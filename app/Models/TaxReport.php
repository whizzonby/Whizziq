<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxReport extends Model
{
    protected $fillable = [
        'user_id',
        'tax_period_id',
        'report_name',
        'report_date',
        'period_start',
        'period_end',
        'total_revenue',
        'total_expenses',
        'total_deductions',
        'taxable_income',
        'estimated_tax',
        'pdf_path',
        'generated_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'total_revenue' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'estimated_tax' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxPeriod(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }

    // Helper Methods
    public function hasPdf(): bool
    {
        return !empty($this->pdf_path) && file_exists(storage_path('app/' . $this->pdf_path));
    }

    public function getPdfUrl(): ?string
    {
        if (!$this->hasPdf()) {
            return null;
        }

        return route('tax.report.download', $this->id);
    }
}
