<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'category',
        'amount',
        'description',
        'tax_category_id',
        'is_tax_deductible',
        'deductible_amount',
        'tax_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'is_tax_deductible' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeTaxDeductible($query)
    {
        return $query->where('is_tax_deductible', true);
    }

    // Helper method to calculate deductible amount
    public function calculateDeductibleAmount(): float
    {
        if (!$this->is_tax_deductible) {
            return 0;
        }

        if ($this->deductible_amount) {
            return (float) $this->deductible_amount;
        }

        if ($this->taxCategory) {
            return $this->taxCategory->calculateDeduction((float) $this->amount);
        }

        return (float) $this->amount;
    }

    // Auto-categorize expense on creation
    protected static function booted(): void
    {
        static::created(function (Expense $expense) {
            // Auto-categorize if no category is set
            if (!$expense->tax_category_id) {
                $autoCategorizationService = app(\App\Services\AutoCategorizationService::class);
                $autoCategorizationService->autoCategorizeNewExpense($expense);
            }
        });
    }
}
