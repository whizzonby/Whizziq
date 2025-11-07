<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'deduction_percentage',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'deduction_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper Methods
    public function calculateDeduction(float $amount): float
    {
        // Validate non-negative amount
        $amount = max(0, $amount);

        // If no deduction percentage, assume 100% deductible
        $percentage = $this->deduction_percentage ?? 100;

        // Calculate deduction
        $deduction = $amount * ($percentage / 100);

        // Ensure deduction doesn't exceed original amount
        return min($deduction, $amount);
    }

    public function getDeductionPercentageFormatted(): string
    {
        return ($this->deduction_percentage ?? 100) . '%';
    }

    public function isFullyDeductible(): bool
    {
        return ($this->deduction_percentage ?? 100) == 100;
    }
}
