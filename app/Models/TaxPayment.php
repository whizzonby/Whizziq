<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'tax_filing_id',
        'tax_year',
        'payment_type',
        'tax_authority',
        'amount',
        'processing_fee',
        'total_amount',
        'payment_method',
        'payment_account_encrypted',
        'scheduled_date',
        'processed_date',
        'due_date',
        'status',
        'status_message',
        'confirmation_number',
        'payment_gateway_id',
        'gateway_response',
        'penalty_amount',
        'interest_amount',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'processed_date' => 'datetime',
        'due_date' => 'date',
        'gateway_response' => 'array',
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxFiling(): BelongsTo
    {
        return $this->belongsTo(TaxFiling::class);
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canCancel(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_date->isFuture();
    }

    public function getPaymentTypeName(): string
    {
        return match($this->payment_type) {
            'balance_due' => 'Balance Due',
            'estimated_q1' => 'Q1 Estimated Payment',
            'estimated_q2' => 'Q2 Estimated Payment',
            'estimated_q3' => 'Q3 Estimated Payment',
            'estimated_q4' => 'Q4 Estimated Payment',
            'extension' => 'Extension Payment',
            'amendment' => 'Amended Return Payment',
            default => 'Tax Payment',
        };
    }

    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    public function isOverdue(): bool
    {
        return $this->getDaysUntilDue() < 0 && !$this->isCompleted();
    }
}
