<?php

namespace App\Services;

use App\Models\RevenueSource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RevenueSourceImportService
{
    /**
     * Import revenue sources from CSV data
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

                $revenueData = array_combine($header, $data);

                // Map CSV columns to database columns
                $mappedData = $this->mapCsvToModel($revenueData, $userId);

                // Validate
                $validator = Validator::make($mappedData, [
                    'date' => 'required|date',
                    'source' => 'required|string|max:255',
                    'amount' => 'required|numeric|min:0',
                    'percentage' => 'nullable|numeric|min:0|max:100',
                ]);

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create revenue source
                RevenueSource::create($mappedData);

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                Log::error("Revenue source import error", ['row' => $index + 2, 'error' => $e->getMessage()]);
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
        $date = $csvData['date'] ?? $csvData['revenue date'] ?? null;
        if ($date) {
            try {
                $date = \Carbon\Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                throw new \Exception("Invalid date format: {$date}");
            }
        }

        $mapped = [
            'user_id' => $userId,
            'date' => $date,
            'source' => $csvData['source'] ?? $csvData['revenue source'] ?? $csvData['source name'] ?? '',
            'amount' => $this->parseNumeric($csvData['amount'] ?? $csvData['revenue amount'] ?? $csvData['value'] ?? 0),
            'percentage' => isset($csvData['percentage']) ? $this->parseNumeric($csvData['percentage']) : null,
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
     * Get CSV template
     */
    public function getCsvTemplate(): string
    {
        $headers = [
            'Date',
            'Source',
            'Amount',
            'Percentage',
        ];

        $example = [
            '2025-01-15',
            'Product Sales',
            '5000.00',
            '45.5',
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
        $requiredColumns = ['date', 'source', 'amount'];

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

