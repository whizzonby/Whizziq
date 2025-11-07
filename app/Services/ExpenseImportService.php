<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ExpenseImportService
{
    /**
     * Import expenses from CSV data
     */
    public function importFromCsv(string $csvContent, int $userId): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Parse CSV
        $lines = str_getcsv($csvContent, "\n");
        $header = str_getcsv(array_shift($lines));

        // Normalize headers
        $header = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);

        foreach ($lines as $index => $line) {
            try {
                $data = str_getcsv($line);

                if (count($data) !== count($header)) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": Column count mismatch";
                    continue;
                }

                $expenseData = array_combine($header, $data);

                // Map CSV columns to database columns
                $mappedData = $this->mapCsvToModel($expenseData, $userId);

                // Validate
                $validator = Validator::make($mappedData, [
                    'date' => 'required|date',
                    'category' => 'required|string|max:255',
                    'amount' => 'required|numeric|min:0',
                    'description' => 'nullable|string|max:500',
                    'is_tax_deductible' => 'nullable|boolean',
                    'deductible_amount' => 'nullable|numeric|min:0',
                    'tax_notes' => 'nullable|string|max:1000',
                ]);

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create expense
                Expense::create($mappedData);

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                Log::error("Expense import error", ['row' => $index + 2, 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Map CSV columns to model attributes
     */
    protected function mapCsvToModel(array $csvData, int $userId): array
    {
        // Parse date - handle various formats
        $date = $csvData['date'] ?? $csvData['expense date'] ?? null;
        if ($date) {
            try {
                $date = \Carbon\Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                throw new \Exception("Invalid date format: {$date}");
            }
        }

        // Parse boolean values
        $isTaxDeductible = $this->parseBoolean($csvData['is tax deductible'] ?? $csvData['tax deductible'] ?? $csvData['tax_deductible'] ?? false);

        $mapped = [
            'user_id' => $userId,
            'date' => $date,
            'category' => $csvData['category'] ?? $csvData['expense category'] ?? '',
            'amount' => $this->parseNumeric($csvData['amount'] ?? $csvData['cost'] ?? $csvData['expense amount'] ?? 0),
            'description' => $csvData['description'] ?? $csvData['notes'] ?? $csvData['details'] ?? null,
            'is_tax_deductible' => $isTaxDeductible,
            'deductible_amount' => $isTaxDeductible ? $this->parseNumeric($csvData['deductible amount'] ?? $csvData['deductible_amount'] ?? null) : null,
            'tax_notes' => $csvData['tax notes'] ?? $csvData['tax_notes'] ?? $csvData['tax notes'] ?? null,
        ];

        // Remove null/empty values for optional fields
        $mapped = array_map(function($value) {
            return $value === '' ? null : $value;
        }, $mapped);

        return $mapped;
    }

    /**
     * Parse numeric value, removing currency symbols and commas
     */
    protected function parseNumeric(?string $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove currency symbols, commas, and whitespace
        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
        
        return $cleaned !== '' ? (float) $cleaned : null;
    }

    /**
     * Parse boolean value from various formats
     */
    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $value = strtolower(trim((string) $value));
        
        return in_array($value, ['yes', 'y', 'true', '1', 'on']);
    }

    /**
     * Get CSV template
     */
    public function getCsvTemplate(): string
    {
        $headers = [
            'Date',
            'Category',
            'Amount',
            'Description',
            'Tax Deductible',
            'Deductible Amount',
            'Tax Notes',
        ];

        $example = [
            '2025-01-15',
            'Office Supplies',
            '125.50',
            'Printer paper and ink cartridges',
            'Yes',
            '125.50',
            'Office supplies are fully deductible',
        ];

        return implode(',', $headers) . "\n" . implode(',', array_map(function($field) {
            return '"' . $field . '"';
        }, $example));
    }

    /**
     * Get Excel template
     */
    public function getExcelTemplate(): string
    {
        // For now, return CSV (can be extended to use PhpSpreadsheet for real Excel)
        return $this->getCsvTemplate();
    }

    /**
     * Validate CSV structure
     */
    public function validateCsvStructure(string $csvContent): array
    {
        $lines = str_getcsv($csvContent, "\n");

        if (count($lines) < 2) {
            return [
                'valid' => false,
                'message' => 'CSV must contain at least a header row and one data row',
            ];
        }

        $header = str_getcsv($lines[0]);
        $requiredColumns = ['date', 'category', 'amount'];

        $headerLower = array_map('strtolower', array_map('trim', $header));

        foreach ($requiredColumns as $required) {
            $found = false;
            foreach ($headerLower as $h) {
                if (str_contains($h, $required)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return [
                    'valid' => false,
                    'message' => "Required column '{$required}' not found in CSV header",
                ];
            }
        }

        return [
            'valid' => true,
            'rows' => count($lines) - 1,
            'columns' => count($header),
        ];
    }
}

