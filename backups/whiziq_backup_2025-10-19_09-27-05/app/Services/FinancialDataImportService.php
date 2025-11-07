<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RevenueSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FinancialDataImportService
{
    /**
     * Import financial data from uploaded file
     *
     * @param string $filePath Full path to the uploaded file
     * @param string $extension File extension (csv, xlsx, xls)
     * @param int $userId User ID for data association
     * @return array ['imported' => int, 'skipped' => int, 'errors' => array]
     */
    public function importFromFile(string $filePath, string $extension, int $userId): array
    {
        $data = match($extension) {
            'csv' => $this->parseCsv($filePath),
            'xlsx', 'xls' => $this->parseExcel($filePath),
            default => throw new \Exception("Unsupported file format: {$extension}"),
        };

        return $this->importData($data, $userId);
    }

    /**
     * Parse CSV file
     */
    protected function parseCsv(string $filePath): array
    {
        $rows = [];
        $headers = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $lineNumber = 0;

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;

                // First row is headers
                if ($lineNumber === 1) {
                    $headers = array_map('strtolower', array_map('trim', $row));
                    continue;
                }

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Combine headers with row data
                $rowData = array_combine($headers, $row);
                $rows[] = $rowData;
            }

            fclose($handle);
        }

        return $rows;
    }

    /**
     * Parse Excel file (basic implementation)
     * Note: For production, consider using PhpSpreadsheet library
     */
    protected function parseExcel(string $filePath): array
    {
        // For now, convert to CSV and parse
        // In production, use PhpSpreadsheet for better Excel support

        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new \Exception('Excel support requires PhpSpreadsheet library. Please run: composer require phpoffice/phpspreadsheet');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];
            $headers = [];

            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // First row is headers
                if ($rowIndex === 1) {
                    $headers = array_map('strtolower', array_map('trim', $rowData));
                    continue;
                }

                // Skip empty rows
                if (empty(array_filter($rowData))) {
                    continue;
                }

                // Combine headers with row data
                $rows[] = array_combine($headers, $rowData);
            }

            return $rows;
        } catch (\Exception $e) {
            throw new \Exception("Failed to parse Excel file: " . $e->getMessage());
        }
    }

    /**
     * Import parsed data into database
     */
    protected function importData(array $rows, int $userId): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $minDate = null;
        $maxDate = null;

        foreach ($rows as $index => $row) {
            try {
                // Validate required fields
                if (!$this->validateRow($row)) {
                    $skipped++;
                    $errors[] = "Row " . ($index + 2) . ": Missing required fields (date, description, amount, type)";
                    continue;
                }

                // Parse date
                $date = $this->parseDate($row['date'] ?? '');
                if (!$date) {
                    $skipped++;
                    $errors[] = "Row " . ($index + 2) . ": Invalid date format";
                    continue;
                }

                // Parse amount
                $amount = $this->parseAmount($row['amount'] ?? '');
                if ($amount === null) {
                    $skipped++;
                    $errors[] = "Row " . ($index + 2) . ": Invalid amount";
                    continue;
                }

                // Determine type
                $type = strtolower(trim($row['type'] ?? ''));
                $description = trim($row['description'] ?? '');
                $category = trim($row['category'] ?? 'Uncategorized');

                // Import based on type
                if (in_array($type, ['expense', 'expenses'])) {
                    $this->importExpense($userId, $date, $description, $amount, $category);
                    $imported++;

                    // Track date range for aggregation
                    if ($minDate === null || $date->lt($minDate)) {
                        $minDate = $date->copy();
                    }
                    if ($maxDate === null || $date->gt($maxDate)) {
                        $maxDate = $date->copy();
                    }
                } elseif (in_array($type, ['revenue', 'income', 'sales'])) {
                    $this->importRevenue($userId, $date, $description, $amount, $category);
                    $imported++;

                    // Track date range for aggregation
                    if ($minDate === null || $date->lt($minDate)) {
                        $minDate = $date->copy();
                    }
                    if ($maxDate === null || $date->gt($maxDate)) {
                        $maxDate = $date->copy();
                    }
                } else {
                    $skipped++;
                    $errors[] = "Row " . ($index + 2) . ": Unknown type '{$type}' (expected 'revenue' or 'expense')";
                }

            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                Log::error('Financial import error', [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ]);
            }
        }

        // Auto-aggregate metrics for imported data
        if ($imported > 0 && $minDate && $maxDate) {
            try {
                $aggregationService = app(BusinessMetricAggregationService::class);
                $aggregationResult = $aggregationService->aggregateForDateRange($userId, $minDate, $maxDate);

                Log::info('Financial data auto-aggregated', [
                    'user_id' => $userId,
                    'imported' => $imported,
                    'date_range' => "{$minDate->toDateString()} to {$maxDate->toDateString()}",
                    'aggregation_result' => $aggregationResult,
                ]);
            } catch (\Exception $e) {
                Log::error('Auto-aggregation failed after import', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the import if aggregation fails
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Validate row has required fields
     */
    protected function validateRow(array $row): bool
    {
        $requiredFields = ['date', 'description', 'amount', 'type'];

        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || empty(trim($row[$field]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(string $dateString): ?Carbon
    {
        try {
            // Try common date formats
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'm-d-Y',
                'Y/m/d',
                'd.m.Y',
            ];

            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, trim($dateString));
                if ($date) {
                    return $date;
                }
            }

            // Try Carbon's flexible parser
            return Carbon::parse(trim($dateString));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse amount from string (handles currency symbols, commas, etc.)
     */
    protected function parseAmount(string $amountString): ?float
    {
        try {
            // Remove currency symbols and spaces
            $cleaned = preg_replace('/[^0-9.,\-]/', '', trim($amountString));

            // Handle different decimal separators
            // If there are multiple commas or dots, assume thousands separator
            if (substr_count($cleaned, ',') > 1 || substr_count($cleaned, '.') > 1) {
                // Remove thousands separators
                $cleaned = str_replace([',', '.'], '', $cleaned);
            } elseif (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
                // Both comma and dot present - determine which is decimal
                $commaPos = strrpos($cleaned, ',');
                $dotPos = strrpos($cleaned, '.');

                if ($commaPos > $dotPos) {
                    // Comma is decimal separator
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    // Dot is decimal separator
                    $cleaned = str_replace(',', '', $cleaned);
                }
            } else {
                // Only one separator - assume it's decimal if last 3 chars or fewer
                $lastComma = strrpos($cleaned, ',');
                $lastDot = strrpos($cleaned, '.');

                if ($lastComma !== false && (strlen($cleaned) - $lastComma) <= 3) {
                    $cleaned = str_replace(',', '.', $cleaned);
                }
            }

            $amount = floatval($cleaned);
            return $amount;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Import expense record
     */
    protected function importExpense(int $userId, Carbon $date, string $description, float $amount, string $category): void
    {
        Expense::create([
            'user_id' => $userId,
            'date' => $date,
            'category' => $category,
            'amount' => abs($amount), // Ensure positive
            'description' => $description,
        ]);
    }

    /**
     * Import revenue record
     */
    protected function importRevenue(int $userId, Carbon $date, string $description, float $amount, string $source): void
    {
        // Use description as source if source is generic
        if (in_array(strtolower($source), ['uncategorized', 'other', 'revenue', 'income'])) {
            $source = $description;
        }

        RevenueSource::create([
            'user_id' => $userId,
            'date' => $date,
            'source' => $source,
            'amount' => abs($amount), // Ensure positive
            'percentage' => 0, // Will be calculated later
        ]);
    }
}
