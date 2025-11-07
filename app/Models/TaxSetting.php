<?php

namespace App\Models;

use App\Services\CountriesService;
use App\Services\SecureDataEncryptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSetting extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'tax_id',
        'business_type',
        'country',
        'state',
        'fiscal_year_end',
        'filing_frequency',
        'tax_rate',
        'auto_categorize',
        'reminder_enabled',
        'reminder_days_before',
        // New fields
        'ssn_encrypted',
        'state_tax_id_encrypted',
        'address_line_1',
        'address_line_2',
        'city',
        'zip_code',
        'filing_status',
        'dependents_count',
        'dependents_data',
        'accounting_method',
        'business_start_date',
        'business_phone',
        'business_email',
        'business_description',
        'naics_code',
        'bank_routing_encrypted',
        'bank_account_encrypted',
        'bank_account_type',
        'has_tax_professional',
        'tax_pro_name',
        'tax_pro_ptin',
        'tax_pro_phone',
        'tax_pro_email',
        'e_file_enabled',
        'auto_file_enabled',
        'paper_file_backup',
        'profile_completed_at',
        'last_verified_at',
        'is_verified',
        'irs_etin',
        'state_registrations',
    ];

    protected $casts = [
        'fiscal_year_end' => 'date',
        'business_start_date' => 'date',
        'tax_rate' => 'decimal:2',
        'auto_categorize' => 'boolean',
        'reminder_enabled' => 'boolean',
        'has_tax_professional' => 'boolean',
        'e_file_enabled' => 'boolean',
        'auto_file_enabled' => 'boolean',
        'paper_file_backup' => 'boolean',
        'is_verified' => 'boolean',
        'profile_completed_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'dependents_data' => 'array',
        'state_registrations' => 'array',
    ];

    protected $hidden = [
        'ssn_encrypted',
        'state_tax_id_encrypted',
        'bank_routing_encrypted',
        'bank_account_encrypted',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // SSN Methods
    public function setSSN(string $ssn): void
    {
        $encryptionService = app(SecureDataEncryptionService::class);
        $this->ssn_encrypted = $encryptionService->encryptSSN($ssn);
    }

    public function getSSN(): ?string
    {
        if (!$this->ssn_encrypted) {
            return null;
        }

        $encryptionService = app(SecureDataEncryptionService::class);
        return $encryptionService->decryptSSN($this->ssn_encrypted);
    }

    public function getMaskedSSN(): string
    {
        $encryptionService = app(SecureDataEncryptionService::class);
        return $encryptionService->getMaskedSSN($this->ssn_encrypted);
    }

    // Bank Account Methods
    public function setBankAccount(string $accountNumber): void
    {
        $encryptionService = app(SecureDataEncryptionService::class);
        $this->bank_account_encrypted = $encryptionService->encryptBankAccount($accountNumber);
    }

    public function getBankAccount(): ?string
    {
        if (!$this->bank_account_encrypted) {
            return null;
        }

        $encryptionService = app(SecureDataEncryptionService::class);
        return $encryptionService->decryptSensitiveData($this->bank_account_encrypted);
    }

    public function getMaskedBankAccount(): string
    {
        $encryptionService = app(SecureDataEncryptionService::class);
        return $encryptionService->getMaskedBankAccount($this->bank_account_encrypted);
    }

    // Routing Number Methods
    public function setBankRouting(string $routingNumber): void
    {
        $encryptionService = app(SecureDataEncryptionService::class);
        $this->bank_routing_encrypted = $encryptionService->encryptRoutingNumber($routingNumber);
    }

    public function getBankRouting(): ?string
    {
        if (!$this->bank_routing_encrypted) {
            return null;
        }

        $encryptionService = app(SecureDataEncryptionService::class);
        return $encryptionService->decryptSensitiveData($this->bank_routing_encrypted);
    }

    // Address Methods
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->zip_code,
        ]);

        return implode(', ', $parts);
    }

    public function hasCompleteAddress(): bool
    {
        return !empty($this->address_line_1) &&
               !empty($this->city) &&
               !empty($this->state) &&
               !empty($this->zip_code);
    }

    // Profile Completion Methods
    public function hasCompletedSetup(): bool
    {
        return $this->hasBasicInfo() &&
               $this->hasCompleteAddress() &&
               $this->hasSSN() &&
               $this->hasBankInfo();
    }

    public function hasBasicInfo(): bool
    {
        return !empty($this->business_name) &&
               !empty($this->tax_id) &&
               !empty($this->business_type) &&
               !empty($this->fiscal_year_end) &&
               !empty($this->filing_status);
    }

    public function hasSSN(): bool
    {
        return !empty($this->ssn_encrypted);
    }

    public function hasBankInfo(): bool
    {
        return !empty($this->bank_account_encrypted) &&
               !empty($this->bank_routing_encrypted) &&
               !empty($this->bank_account_type);
    }

    public function getCompletionPercentage(): int
    {
        $total = 4; // Number of completion checks
        $completed = 0;

        if ($this->hasBasicInfo()) $completed++;
        if ($this->hasCompleteAddress()) $completed++;
        if ($this->hasSSN()) $completed++;
        if ($this->hasBankInfo()) $completed++;

        return (int) round(($completed / $total) * 100);
    }

    public function getMissingRequirements(): array
    {
        $missing = [];

        if (!$this->hasBasicInfo()) {
            $missing[] = 'Complete basic business information (name, tax ID, business type, filing status)';
        }

        if (!$this->hasCompleteAddress()) {
            $missing[] = 'Provide complete mailing address';
        }

        if (!$this->hasSSN()) {
            $missing[] = 'Enter Social Security Number';
        }

        if (!$this->hasBankInfo()) {
            $missing[] = 'Add bank account for refunds/payments';
        }

        return $missing;
    }

    public function markProfileCompleted(): void
    {
        if ($this->hasCompletedSetup() && !$this->profile_completed_at) {
            $this->profile_completed_at = now();
            $this->save();
        }
    }

    public function getCurrentTaxYear(): int
    {
        if (!$this->fiscal_year_end) {
            return now()->year;
        }

        return $this->fiscal_year_end->year;
    }

    public function getFilingStatusName(): string
    {
        return match($this->filing_status) {
            'single' => 'Single',
            'married_joint' => 'Married Filing Jointly',
            'married_separate' => 'Married Filing Separately',
            'head_of_household' => 'Head of Household',
            'qualifying_widow' => 'Qualifying Widow(er)',
            default => 'Not Set',
        };
    }

    public function getBusinessTypeName(): string
    {
        return match($this->business_type) {
            'sole_proprietor' => 'Sole Proprietor',
            'partnership' => 'Partnership',
            'llc' => 'LLC',
            's_corp' => 'S Corporation',
            'c_corp' => 'C Corporation',
            default => 'Not Set',
        };
    }

    /**
     * Get the country name from the country code
     * 
     * @return string|null
     */
    public function getCountryNameAttribute(): ?string
    {
        return CountriesService::getCountryName($this->country);
    }
}

