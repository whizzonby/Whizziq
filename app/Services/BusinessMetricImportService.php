<?php

namespace App\Services;

use App\Models\BusinessMetric;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BusinessMetricImportService
{
    /**
     * Import business metrics from CSV data
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

                $metricData = array_combine($header, $data);

                // Map CSV columns to database columns
                $mappedData = $this->mapCsvToModel($metricData, $userId);

                // Validate
                $validator = Validator::make($mappedData, [
                    'date' => 'required|date',
                    'revenue' => 'required|numeric|min:0',
                    'profit' => 'required|numeric',
                    'expenses' => 'required|numeric|min:0',
                    'cash_flow' => 'required|numeric',
                    'revenue_change_percentage' => 'nullable|numeric',
                    'profit_change_percentage' => 'nullable|numeric',
                    'expenses_change_percentage' => 'nullable|numeric',
                    'cash_flow_change_percentage' => 'nullable|numeric',
                ]);

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create or update business metric (update if same date exists)
                $metric = BusinessMetric::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'date' => $mappedData['date'],
                    ],
                    $mappedData
                );

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                Log::error("Business metric import error", ['row' => $index + 2, 'error' => $e->getMessage()]);
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
        $date = $csvData['date'] ?? $csvData['date recorded'] ?? null;
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
            'revenue' => $this->parseNumeric($csvData['revenue'] ?? $csvData['total revenue'] ?? 0),
            'profit' => $this->parseNumeric($csvData['profit'] ?? $csvData['net profit'] ?? 0),
            'expenses' => $this->parseNumeric($csvData['expenses'] ?? $csvData['total expenses'] ?? 0),
            'cash_flow' => $this->parseNumeric($csvData['cash flow'] ?? $csvData['cash_flow'] ?? 0),
            'revenue_change_percentage' => $this->parseNumeric($csvData['revenue change %'] ?? $csvData['revenue_change_percentage'] ?? $csvData['revenue change percentage'] ?? null),
            'profit_change_percentage' => $this->parseNumeric($csvData['profit change %'] ?? $csvData['profit_change_percentage'] ?? $csvData['profit change percentage'] ?? null),
            'expenses_change_percentage' => $this->parseNumeric($csvData['expenses change %'] ?? $csvData['expenses_change_percentage'] ?? $csvData['expenses change percentage'] ?? null),
            'cash_flow_change_percentage' => $this->parseNumeric($csvData['cash flow change %'] ?? $csvData['cash_flow_change_percentage'] ?? $csvData['cash flow change percentage'] ?? null),
        ];

        // Remove null values for optional fields
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
            'Revenue',
            'Profit',
            'Expenses',
            'Cash Flow',
            'Revenue Change %',
            'Profit Change %',
            'Expenses Change %',
            'Cash Flow Change %',
        ];

        $example = [
            '2025-01-15',
            '50000.00',
            '15000.00',
            '35000.00',
            '10000.00',
            '5.5',
            '10.2',
            '3.1',
            '8.9',
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
        $requiredColumns = ['date', 'revenue', 'profit', 'expenses', 'cash flow'];

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

