<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_invoice_id',
        'invoice_client_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ClientInvoice::class, 'client_invoice_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(InvoiceClient::class, 'invoice_client_id');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('payment_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeThisYear($query)
    {
        return $query->whereBetween('payment_date', [
            now()->startOfYear(),
            now()->endOfYear(),
        ]);
    }

    // Accessors
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'check' => 'Check',
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'other' => 'Other',
            default => ucfirst($this->payment_method),
        };
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($payment) {
            // Update invoice payment status when payment is created
            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            }
        });

        static::deleted(function ($payment) {
            // Update invoice payment status when payment is deleted
            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            }
        });
    }
}
