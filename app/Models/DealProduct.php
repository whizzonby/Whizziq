<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'product_name',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function getDiscountedSubtotalAttribute(): float
    {
        $subtotal = $this->subtotal;

        if ($this->discount_amount > 0) {
            return $subtotal - $this->discount_amount;
        }

        if ($this->discount_percent > 0) {
            return $subtotal - ($subtotal * $this->discount_percent / 100);
        }

        return $subtotal;
    }

    public function getTotalAttribute(): float
    {
        $discountedSubtotal = $this->discounted_subtotal;

        if ($this->tax_amount > 0) {
            return $discountedSubtotal + $this->tax_amount;
        }

        if ($this->tax_percent > 0) {
            return $discountedSubtotal + ($discountedSubtotal * $this->tax_percent / 100);
        }

        return $discountedSubtotal;
    }

    // Update line total when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            $product->line_total = $product->total;
        });

        static::saved(function ($product) {
            // Update deal value when product is saved
            $product->deal->updateTotalFromProducts();
        });

        static::deleted(function ($product) {
            // Update deal value when product is deleted
            $product->deal->updateTotalFromProducts();
        });
    }
}
