<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ClientInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'invoice_client_id',
        'invoice_number',
        'status',
        'invoice_date',
        'due_date',
        'paid_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'currency',
        'notes',
        'terms',
        'footer',
        'last_reminder_sent_at',
        'reminder_count',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'last_reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(InvoiceClient::class, 'invoice_client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientInvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class)->orderBy('payment_date', 'desc');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'partial', 'overdue']);
    }

    public function scopeDueThisMonth($query)
    {
        return $query->whereBetween('due_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereBetween('due_date', [
            now(),
            now()->addDays($days),
        ])->whereIn('status', ['sent', 'partial']);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false) * -1;
    }

    public function getDaysUntilDueAttribute(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'partial' => 'yellow',
            'overdue' => 'red',
            'paid' => 'green',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getAgingBucketAttribute(): string
    {
        if ($this->status === 'paid' || !$this->is_overdue) {
            return 'current';
        }

        $daysOverdue = $this->days_overdue;

        return match(true) {
            $daysOverdue <= 30 => '0-30',
            $daysOverdue <= 60 => '31-60',
            $daysOverdue <= 90 => '61-90',
            default => '90+',
        };
    }

    // Business Logic Methods
    public function calculateTotals(): void
    {
        // Calculate subtotal from items
        $this->subtotal = $this->items()->sum('amount');

        // Calculate tax amount
        $this->tax_amount = ($this->subtotal * $this->tax_rate) / 100;

        // Calculate total
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;

        // Calculate balance due
        $this->balance_due = $this->total_amount - $this->amount_paid;
    }

    public function recordPayment(float $amount, string $paymentMethod, ?string $transactionId = null, ?string $notes = null, ?Carbon $paymentDate = null): ClientPayment
    {
        $payment = $this->payments()->create([
            'user_id' => $this->user_id,
            'invoice_client_id' => $this->invoice_client_id,
            'amount' => $amount,
            'payment_date' => $paymentDate ?? now(),
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'notes' => $notes,
        ]);

        $this->updatePaymentStatus();

        return $payment;
    }

    public function updatePaymentStatus(): void
    {
        // Recalculate amount paid
        $this->amount_paid = $this->payments()->sum('amount');
        $this->balance_due = $this->total_amount - $this->amount_paid;

        // Update status based on payment
        if ($this->balance_due <= 0) {
            $this->status = 'paid';
            $this->paid_date = now();
        } elseif ($this->amount_paid > 0 && $this->balance_due > 0) {
            $this->status = 'partial';
        } elseif ($this->is_overdue && $this->status !== 'paid') {
            $this->status = 'overdue';
        }

        $this->save();
    }

    public function markAsSent(): void
    {
        if ($this->status === 'draft') {
            $this->status = 'sent';
            $this->save();
        }
    }

    public function markAsPaid(?Carbon $paidDate = null): void
    {
        $this->status = 'paid';
        $this->paid_date = $paidDate ?? now();
        $this->balance_due = 0;
        $this->amount_paid = $this->total_amount;
        $this->save();
    }

    public function markAsCancelled(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function checkAndUpdateOverdueStatus(): void
    {
        if ($this->is_overdue && $this->status !== 'paid' && $this->status !== 'cancelled') {
            $this->status = 'overdue';
            $this->save();
        }
    }

    public function canSendReminder(): bool
    {
        // Don't send reminders for draft, paid, or cancelled invoices
        if (in_array($this->status, ['draft', 'paid', 'cancelled'])) {
            return false;
        }

        // If never sent a reminder, can send
        if (!$this->last_reminder_sent_at) {
            return true;
        }

        // Only send if at least 24 hours since last reminder
        return $this->last_reminder_sent_at->diffInHours(now()) >= 24;
    }

    public function recordReminderSent(): void
    {
        $this->last_reminder_sent_at = now();
        $this->reminder_count++;
        $this->save();
    }

    // Static Methods
    public static function generateInvoiceNumber(?string $prefix = 'INV'): string
    {
        $lastInvoice = static::latest('id')->first();
        $number = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, strlen($prefix) + 1)) + 1) : 1;

        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    // Boot method for auto-calculations
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($invoice) {
            // Auto-calculate totals if items exist
            if ($invoice->exists && $invoice->items()->count() > 0) {
                $invoice->calculateTotals();
            }

            // Auto-update overdue status
            $invoice->checkAndUpdateOverdueStatus();
        });
    }
}
