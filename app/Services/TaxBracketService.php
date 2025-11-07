<?php

namespace App\Services;

use App\Models\TaxSetting;

class TaxBracketService
{
    /**
     * Calculate federal tax using 2024 progressive tax brackets
     */
    public function calculateFederalTax(float $taxableIncome, string $filingStatus = 'single'): array
    {
        // 2024 Federal Tax Brackets
        $brackets = $this->getFederalTaxBrackets($filingStatus);

        $totalTax = 0;
        $effectiveRate = 0;
        $breakdown = [];

        foreach ($brackets as $bracket) {
            if ($taxableIncome <= 0) {
                break;
            }

            $bracketIncome = 0;

            if ($taxableIncome > $bracket['upper_limit']) {
                // Income fills this entire bracket
                $bracketIncome = $bracket['upper_limit'] - $bracket['lower_limit'];
            } else {
                // Income partially fills this bracket
                $bracketIncome = $taxableIncome - $bracket['lower_limit'];
            }

            if ($bracketIncome > 0) {
                $bracketTax = $bracketIncome * ($bracket['rate'] / 100);
                $totalTax += $bracketTax;

                $breakdown[] = [
                    'bracket' => $bracket['rate'] . '%',
                    'income' => round($bracketIncome, 2),
                    'tax' => round($bracketTax, 2),
                ];
            }
        }

        if ($taxableIncome > 0) {
            $effectiveRate = ($totalTax / $taxableIncome) * 100;
        }

        return [
            'total_tax' => round($totalTax, 2),
            'effective_rate' => round($effectiveRate, 2),
            'breakdown' => $breakdown,
            'filing_status' => $filingStatus,
        ];
    }

    /**
     * Calculate state tax (supports major states)
     */
    public function calculateStateTax(float $taxableIncome, string $state): array
    {
        $stateTaxRates = $this->getStateTaxRates();

        if (!isset($stateTaxRates[$state])) {
            return [
                'total_tax' => 0,
                'effective_rate' => 0,
                'note' => 'No state income tax or state not supported',
            ];
        }

        $stateData = $stateTaxRates[$state];

        if ($stateData['type'] === 'flat') {
            // Flat tax rate (e.g., Illinois, Colorado)
            $tax = $taxableIncome * ($stateData['rate'] / 100);
            return [
                'total_tax' => round($tax, 2),
                'effective_rate' => $stateData['rate'],
                'type' => 'flat',
                'rate' => $stateData['rate'],
            ];
        } else {
            // Progressive brackets (e.g., California, New York)
            return $this->calculateProgressiveStateTax($taxableIncome, $stateData['brackets']);
        }
    }

    /**
     * Calculate total tax (federal + state)
     */
    public function calculateTotalTax(float $taxableIncome, TaxSetting $taxSetting): array
    {
        $federalTax = $this->calculateFederalTax($taxableIncome, $taxSetting->filing_status ?? 'single');
        $stateTax = $this->calculateStateTax($taxableIncome, $taxSetting->state ?? 'CA');

        return [
            'taxable_income' => round($taxableIncome, 2),
            'federal_tax' => $federalTax['total_tax'],
            'state_tax' => $stateTax['total_tax'],
            'total_tax' => round($federalTax['total_tax'] + $stateTax['total_tax'], 2),
            'federal_effective_rate' => $federalTax['effective_rate'],
            'state_effective_rate' => $stateTax['effective_rate'] ?? 0,
            'overall_effective_rate' => $taxableIncome > 0
                ? round((($federalTax['total_tax'] + $stateTax['total_tax']) / $taxableIncome) * 100, 2)
                : 0,
            'federal_breakdown' => $federalTax['breakdown'],
            'filing_status' => $federalTax['filing_status'],
        ];
    }

    /**
     * Get 2024 federal tax brackets by filing status
     */
    protected function getFederalTaxBrackets(string $filingStatus): array
    {
        $brackets = [
            'single' => [
                ['rate' => 10, 'lower_limit' => 0, 'upper_limit' => 11600],
                ['rate' => 12, 'lower_limit' => 11600, 'upper_limit' => 47150],
                ['rate' => 22, 'lower_limit' => 47150, 'upper_limit' => 100525],
                ['rate' => 24, 'lower_limit' => 100525, 'upper_limit' => 191950],
                ['rate' => 32, 'lower_limit' => 191950, 'upper_limit' => 243725],
                ['rate' => 35, 'lower_limit' => 243725, 'upper_limit' => 609350],
                ['rate' => 37, 'lower_limit' => 609350, 'upper_limit' => PHP_FLOAT_MAX],
            ],
            'married_joint' => [
                ['rate' => 10, 'lower_limit' => 0, 'upper_limit' => 23200],
                ['rate' => 12, 'lower_limit' => 23200, 'upper_limit' => 94300],
                ['rate' => 22, 'lower_limit' => 94300, 'upper_limit' => 201050],
                ['rate' => 24, 'lower_limit' => 201050, 'upper_limit' => 383900],
                ['rate' => 32, 'lower_limit' => 383900, 'upper_limit' => 487450],
                ['rate' => 35, 'lower_limit' => 487450, 'upper_limit' => 731200],
                ['rate' => 37, 'lower_limit' => 731200, 'upper_limit' => PHP_FLOAT_MAX],
            ],
            'married_separate' => [
                ['rate' => 10, 'lower_limit' => 0, 'upper_limit' => 11600],
                ['rate' => 12, 'lower_limit' => 11600, 'upper_limit' => 47150],
                ['rate' => 22, 'lower_limit' => 47150, 'upper_limit' => 100525],
                ['rate' => 24, 'lower_limit' => 100525, 'upper_limit' => 191950],
                ['rate' => 32, 'lower_limit' => 191950, 'upper_limit' => 243725],
                ['rate' => 35, 'lower_limit' => 243725, 'upper_limit' => 365600],
                ['rate' => 37, 'lower_limit' => 365600, 'upper_limit' => PHP_FLOAT_MAX],
            ],
            'head_of_household' => [
                ['rate' => 10, 'lower_limit' => 0, 'upper_limit' => 16550],
                ['rate' => 12, 'lower_limit' => 16550, 'upper_limit' => 63100],
                ['rate' => 22, 'lower_limit' => 63100, 'upper_limit' => 100500],
                ['rate' => 24, 'lower_limit' => 100500, 'upper_limit' => 191950],
                ['rate' => 32, 'lower_limit' => 191950, 'upper_limit' => 243700],
                ['rate' => 35, 'lower_limit' => 243700, 'upper_limit' => 609350],
                ['rate' => 37, 'lower_limit' => 609350, 'upper_limit' => PHP_FLOAT_MAX],
            ],
        ];

        return $brackets[$filingStatus] ?? $brackets['single'];
    }

    /**
     * Get state tax rates (major states)
     */
    protected function getStateTaxRates(): array
    {
        return [
            // No state income tax
            'AK' => ['type' => 'none', 'rate' => 0],
            'FL' => ['type' => 'none', 'rate' => 0],
            'NV' => ['type' => 'none', 'rate' => 0],
            'SD' => ['type' => 'none', 'rate' => 0],
            'TN' => ['type' => 'none', 'rate' => 0],
            'TX' => ['type' => 'none', 'rate' => 0],
            'WA' => ['type' => 'none', 'rate' => 0],
            'WY' => ['type' => 'none', 'rate' => 0],

            // Flat tax states
            'CO' => ['type' => 'flat', 'rate' => 4.40],
            'IL' => ['type' => 'flat', 'rate' => 4.95],
            'IN' => ['type' => 'flat', 'rate' => 3.15],
            'KY' => ['type' => 'flat', 'rate' => 4.50],
            'MA' => ['type' => 'flat', 'rate' => 5.00],
            'MI' => ['type' => 'flat', 'rate' => 4.25],
            'NC' => ['type' => 'flat', 'rate' => 4.50],
            'PA' => ['type' => 'flat', 'rate' => 3.07],
            'UT' => ['type' => 'flat', 'rate' => 4.85],

            // Progressive tax states (simplified - use middle rate)
            'CA' => ['type' => 'progressive', 'brackets' => [
                ['rate' => 1.00, 'upper_limit' => 10412],
                ['rate' => 2.00, 'upper_limit' => 24684],
                ['rate' => 4.00, 'upper_limit' => 38959],
                ['rate' => 6.00, 'upper_limit' => 54081],
                ['rate' => 8.00, 'upper_limit' => 68350],
                ['rate' => 9.30, 'upper_limit' => 349137],
                ['rate' => 10.30, 'upper_limit' => 418961],
                ['rate' => 11.30, 'upper_limit' => 698271],
                ['rate' => 12.30, 'upper_limit' => PHP_FLOAT_MAX],
            ]],
            'NY' => ['type' => 'progressive', 'brackets' => [
                ['rate' => 4.00, 'upper_limit' => 8500],
                ['rate' => 4.50, 'upper_limit' => 11700],
                ['rate' => 5.25, 'upper_limit' => 13900],
                ['rate' => 5.85, 'upper_limit' => 80650],
                ['rate' => 6.25, 'upper_limit' => 215400],
                ['rate' => 6.85, 'upper_limit' => 1077550],
                ['rate' => 9.65, 'upper_limit' => 5000000],
                ['rate' => 10.30, 'upper_limit' => 25000000],
                ['rate' => 10.90, 'upper_limit' => PHP_FLOAT_MAX],
            ]],
        ];
    }

    /**
     * Calculate progressive state tax
     */
    protected function calculateProgressiveStateTax(float $taxableIncome, array $brackets): array
    {
        $totalTax = 0;
        $previousLimit = 0;

        foreach ($brackets as $bracket) {
            if ($taxableIncome <= $previousLimit) {
                break;
            }

            $bracketIncome = min($taxableIncome, $bracket['upper_limit']) - $previousLimit;

            if ($bracketIncome > 0) {
                $totalTax += $bracketIncome * ($bracket['rate'] / 100);
            }

            $previousLimit = $bracket['upper_limit'];
        }

        return [
            'total_tax' => round($totalTax, 2),
            'effective_rate' => $taxableIncome > 0 ? round(($totalTax / $taxableIncome) * 100, 2) : 0,
            'type' => 'progressive',
        ];
    }

    /**
     * Get marginal tax rate for income level
     */
    public function getMarginalTaxRate(float $taxableIncome, string $filingStatus = 'single'): float
    {
        $brackets = $this->getFederalTaxBrackets($filingStatus);

        foreach ($brackets as $bracket) {
            if ($taxableIncome <= $bracket['upper_limit']) {
                return $bracket['rate'];
            }
        }

        return 37; // Highest bracket
    }

    /**
     * Estimate tax savings from additional deduction
     */
    public function estimateTaxSavings(float $deductionAmount, float $currentIncome, string $filingStatus = 'single'): array
    {
        $currentTax = $this->calculateFederalTax($currentIncome, $filingStatus);
        $newTax = $this->calculateFederalTax($currentIncome - $deductionAmount, $filingStatus);

        $savings = $currentTax['total_tax'] - $newTax['total_tax'];

        return [
            'deduction_amount' => round($deductionAmount, 2),
            'tax_savings' => round($savings, 2),
            'effective_savings_rate' => $deductionAmount > 0 ? round(($savings / $deductionAmount) * 100, 2) : 0,
            'new_taxable_income' => round($currentIncome - $deductionAmount, 2),
            'new_total_tax' => $newTax['total_tax'],
        ];
    }
}
