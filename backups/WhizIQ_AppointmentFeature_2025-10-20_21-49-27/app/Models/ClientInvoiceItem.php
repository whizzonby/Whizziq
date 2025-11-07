<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ClientInvoice::class, 'client_invoice_id');
    }

    // Business Logic
    public function calculateAmount(): void
    {
        $this->amount = $this->quantity * $this->unit_price;
    }

    // Boot method for auto-calculations
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate amount
            $item->calculateAmount();
        });

        static::saved(function ($item) {
            // Recalculate invoice totals when item is saved
            if ($item->invoice) {
                $item->invoice->calculateTotals();
                $item->invoice->save();
            }
        });

        static::deleted(function ($item) {
            // Recalculate invoice totals when item is deleted
            if ($item->invoice) {
                $item->invoice->calculateTotals();
                $item->invoice->save();
            }
        });
    }
}
