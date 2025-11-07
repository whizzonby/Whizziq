<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxFiling extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'tax_period_id',
        'tax_year',
        'filing_type',
        'filing_method',
        'status',
        'status_message',
        'submitted_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'federal_confirmation_number',
        'state_confirmation_number',
        'api_response',
        'total_income',
        'total_deductions',
        'taxable_income',
        'total_tax',
        'federal_withholding',
        'estimated_payments',
        'refund_amount',
        'amount_owed',
        'state_taxable_income',
        'state_tax',
        'state_withholding',
        'state_refund',
        'state_owed',
        'forms_included',
        'pdf_paths',
        'payment_method',
        'payment_scheduled_at',
        'payment_processed_at',
        'payment_confirmation',
        'prepared_by',
        'reviewed_by',
        'calculation_details',
        'audit_log',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'payment_scheduled_at' => 'datetime',
        'payment_processed_at' => 'datetime',
        'api_response' => 'array',
        'forms_included' => 'array',
        'pdf_paths' => 'array',
        'calculation_details' => 'array',
        'audit_log' => 'array',
        'total_income' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'federal_withholding' => 'decimal:2',
        'estimated_payments' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'amount_owed' => 'decimal:2',
        'state_taxable_income' => 'decimal:2',
        'state_tax' => 'decimal:2',
        'state_withholding' => 'decimal:2',
        'state_refund' => 'decimal:2',
        'state_owed' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxPeriod(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TaxPayment::class);
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'pending', 'accepted', 'completed']);
    }

    public function isAccepted(): bool
    {
        return in_array($this->status, ['accepted', 'completed']);
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'ready', 'rejected']);
    }

    public function hasRefund(): bool
    {
        return $this->refund_amount > 0;
    }

    public function hasBalance(): bool
    {
        return $this->amount_owed > 0;
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'ready' => 'blue',
            'submitted', 'pending' => 'yellow',
            'accepted', 'completed' => 'green',
            'rejected' => 'red',
            'amended' => 'purple',
            default => 'gray',
        };
    }

    public function addToAuditLog(string $action, ?string $details = null): void
    {
        $log = $this->audit_log ?? [];

        $log[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        $this->audit_log = $log;
        $this->save();
    }
}
