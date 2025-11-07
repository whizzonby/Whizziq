<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class SecureDataEncryptionService
{
    /**
     * Encrypt sensitive data (SSN, bank account, etc.)
     */
    public function encryptSensitiveData(string $data): string
    {
        return Crypt::encryptString($data);
    }

    /**
     * Decrypt sensitive data
     */
    public function decryptSensitiveData(string $encryptedData): ?string
    {
        try {
            return Crypt::decryptString($encryptedData);
        } catch (DecryptException $e) {
            \Log::error('Failed to decrypt sensitive data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Encrypt SSN and format it
     */
    public function encryptSSN(string $ssn): string
    {
        // Remove any formatting
        $cleanSSN = preg_replace('/[^0-9]/', '', $ssn);

        // Validate SSN format (9 digits)
        if (strlen($cleanSSN) !== 9) {
            throw new \InvalidArgumentException('Invalid SSN format. Must be 9 digits.');
        }

        return $this->encryptSensitiveData($cleanSSN);
    }

    /**
     * Decrypt SSN
     */
    public function decryptSSN(?string $encryptedSSN): ?string
    {
        if (!$encryptedSSN) {
            return null;
        }

        return $this->decryptSensitiveData($encryptedSSN);
    }

    /**
     * Get masked SSN for display (XXX-XX-1234)
     */
    public function getMaskedSSN(?string $encryptedSSN): string
    {
        if (!$encryptedSSN) {
            return 'XXX-XX-XXXX';
        }

        $ssn = $this->decryptSSN($encryptedSSN);

        if (!$ssn || strlen($ssn) !== 9) {
            return 'XXX-XX-XXXX';
        }

        $last4 = substr($ssn, -4);
        return "XXX-XX-{$last4}";
    }

    /**
     * Format SSN for display (with dashes)
     */
    public function formatSSN(string $ssn): string
    {
        $cleanSSN = preg_replace('/[^0-9]/', '', $ssn);

        if (strlen($cleanSSN) !== 9) {
            return $ssn;
        }

        return substr($cleanSSN, 0, 3) . '-' .
               substr($cleanSSN, 3, 2) . '-' .
               substr($cleanSSN, 5, 4);
    }

    /**
     * Encrypt bank account number
     */
    public function encryptBankAccount(string $accountNumber): string
    {
        $cleanAccount = preg_replace('/[^0-9]/', '', $accountNumber);

        if (strlen($cleanAccount) < 4 || strlen($cleanAccount) > 17) {
            throw new \InvalidArgumentException('Invalid bank account number format.');
        }

        return $this->encryptSensitiveData($cleanAccount);
    }

    /**
     * Get masked bank account for display
     */
    public function getMaskedBankAccount(?string $encryptedAccount): string
    {
        if (!$encryptedAccount) {
            return '****';
        }

        $account = $this->decryptSensitiveData($encryptedAccount);

        if (!$account) {
            return '****';
        }

        $last4 = substr($account, -4);
        return "****{$last4}";
    }

    /**
     * Encrypt routing number
     */
    public function encryptRoutingNumber(string $routingNumber): string
    {
        $cleanRouting = preg_replace('/[^0-9]/', '', $routingNumber);

        // Validate routing number (9 digits)
        if (strlen($cleanRouting) !== 9) {
            throw new \InvalidArgumentException('Invalid routing number format. Must be 9 digits.');
        }

        // Validate routing number using checksum algorithm
        if (!$this->validateRoutingNumber($cleanRouting)) {
            throw new \InvalidArgumentException('Invalid routing number checksum.');
        }

        return $this->encryptSensitiveData($cleanRouting);
    }

    /**
     * Validate routing number using ABA checksum
     */
    protected function validateRoutingNumber(string $routingNumber): bool
    {
        if (strlen($routingNumber) !== 9) {
            return false;
        }

        $checksum = (
            3 * ($routingNumber[0] + $routingNumber[3] + $routingNumber[6]) +
            7 * ($routingNumber[1] + $routingNumber[4] + $routingNumber[7]) +
            1 * ($routingNumber[2] + $routingNumber[5] + $routingNumber[8])
        ) % 10;

        return $checksum === 0;
    }

    /**
     * Encrypt EIN (Employer Identification Number)
     */
    public function encryptEIN(string $ein): string
    {
        $cleanEIN = preg_replace('/[^0-9]/', '', $ein);

        // Validate EIN format (9 digits)
        if (strlen($cleanEIN) !== 9) {
            throw new \InvalidArgumentException('Invalid EIN format. Must be 9 digits.');
        }

        return $this->encryptSensitiveData($cleanEIN);
    }

    /**
     * Get masked EIN for display
     */
    public function getMaskedEIN(?string $encryptedEIN): string
    {
        if (!$encryptedEIN) {
            return 'XX-XXXXXXX';
        }

        $ein = $this->decryptSensitiveData($encryptedEIN);

        if (!$ein || strlen($ein) !== 9) {
            return 'XX-XXXXXXX';
        }

        return substr($ein, 0, 2) . '-XXXXXXX';
    }

    /**
     * Format EIN for display
     */
    public function formatEIN(string $ein): string
    {
        $cleanEIN = preg_replace('/[^0-9]/', '', $ein);

        if (strlen($cleanEIN) !== 9) {
            return $ein;
        }

        return substr($cleanEIN, 0, 2) . '-' . substr($cleanEIN, 2, 7);
    }

    /**
     * Validate SSN format
     */
    public function validateSSN(string $ssn): bool
    {
        $cleanSSN = preg_replace('/[^0-9]/', '', $ssn);

        if (strlen($cleanSSN) !== 9) {
            return false;
        }

        // Check for invalid patterns
        if ($cleanSSN === '000000000' || $cleanSSN === '123456789') {
            return false;
        }

        // First three digits cannot be 000, 666, or 900-999
        $first3 = substr($cleanSSN, 0, 3);
        if ($first3 === '000' || $first3 === '666' || $first3 >= '900') {
            return false;
        }

        // Middle two digits cannot be 00
        $middle2 = substr($cleanSSN, 3, 2);
        if ($middle2 === '00') {
            return false;
        }

        // Last four digits cannot be 0000
        $last4 = substr($cleanSSN, 5, 4);
        if ($last4 === '0000') {
            return false;
        }

        return true;
    }
}
