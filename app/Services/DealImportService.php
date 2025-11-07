<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Support\Facades\Validator;

class DealImportService
{
    /**
     * Import deals from CSV content
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

        if (empty($lines)) {
            throw new \Exception('CSV file is empty');
        }

        // Extract header
        $header = str_getcsv(array_shift($lines));

        // Process each row
        foreach ($lines as $index => $line) {
            if (empty(trim($line))) {
                continue; // Skip empty lines
            }

            $data = str_getcsv($line);

            // Ensure data matches header length
            if (count($data) !== count($header)) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 2) . ": Column count mismatch";
                continue;
            }

            $dealData = array_combine($header, $data);

            try {
                $mappedData = $this->mapCsvToModel($dealData, $userId);

                // Validate
                $validator = Validator::make($mappedData, [
                    'user_id' => 'required|exists:users,id',
                    'contact_id' => 'nullable|exists:contacts,id',
                    'title' => 'required|string|max:255',
                    'value' => 'required|numeric|min:0',
                    'stage' => 'required|in:lead,qualified,proposal,negotiation,won,lost',
                    'expected_close_date' => 'nullable|date',
                ]);

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create deal
                Deal::create($mappedData);
                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Map CSV columns to model attributes
     */
    protected function mapCsvToModel(array $csvData, int $userId): array
    {
        // Find contact by name or email
        $contactId = null;
        if (!empty($csvData['contact_name']) || !empty($csvData['Contact Name']) || !empty($csvData['contact'])) {
            $contactName = $csvData['contact_name'] ?? $csvData['Contact Name'] ?? $csvData['contact'] ?? null;
            if ($contactName) {
                $contact = Contact::where('user_id', $userId)
                    ->where('name', 'like', '%' . trim($contactName) . '%')
                    ->first();
                $contactId = $contact?->id;
            }
        }

        // Try to find contact by email if name lookup failed
        if (!$contactId && (!empty($csvData['contact_email']) || !empty($csvData['Contact Email']))) {
            $contactEmail = $csvData['contact_email'] ?? $csvData['Contact Email'] ?? null;
            if ($contactEmail) {
                $contact = Contact::where('user_id', $userId)
                    ->where('email', trim($contactEmail))
                    ->first();
                $contactId = $contact?->id;
            }
        }

        return [
            'user_id' => $userId,
            'contact_id' => $contactId,
            'title' => $csvData['title'] ?? $csvData['Title'] ?? $csvData['deal_title'] ?? $csvData['Deal Title'] ?? '',
            'description' => $csvData['description'] ?? $csvData['Description'] ?? null,
            'stage' => strtolower($csvData['stage'] ?? $csvData['Stage'] ?? 'lead'),
            'value' => floatval($csvData['value'] ?? $csvData['Value'] ?? $csvData['amount'] ?? $csvData['Amount'] ?? 0),
            'currency' => $csvData['currency'] ?? $csvData['Currency'] ?? 'USD',
            'probability' => isset($csvData['probability']) ? intval($csvData['probability']) : (isset($csvData['Probability']) ? intval($csvData['Probability']) : $this->getDefaultProbability($csvData['stage'] ?? 'lead')),
            'expected_close_date' => $this->parseDate($csvData['expected_close_date'] ?? $csvData['Expected Close Date'] ?? $csvData['close_date'] ?? $csvData['Close Date'] ?? null),
            'actual_close_date' => $this->parseDate($csvData['actual_close_date'] ?? $csvData['Actual Close Date'] ?? null),
            'source' => $csvData['source'] ?? $csvData['Source'] ?? null,
            'priority' => strtolower($csvData['priority'] ?? $csvData['Priority'] ?? 'medium'),
            'loss_reason' => $csvData['loss_reason'] ?? $csvData['Loss Reason'] ?? null,
            'notes' => $csvData['notes'] ?? $csvData['Notes'] ?? null,
        ];
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get default probability based on stage
     */
    protected function getDefaultProbability(string $stage): int
    {
        return match(strtolower($stage)) {
            'lead' => 20,
            'qualified' => 40,
            'proposal' => 60,
            'negotiation' => 80,
            'won' => 100,
            'lost' => 0,
            default => 20,
        };
    }

    /**
     * Validate CSV structure
     */
    public function validateCsvStructure(string $csvContent): array
    {
        $lines = str_getcsv($csvContent, "\n");

        if (empty($lines)) {
            return [
                'valid' => false,
                'message' => 'CSV file is empty',
            ];
        }

        $header = str_getcsv($lines[0]);

        // Check for required columns (flexible)
        $hasTitle = false;
        $hasValue = false;

        foreach ($header as $column) {
            $column = strtolower(trim($column));
            if (in_array($column, ['title', 'deal title', 'deal_title'])) {
                $hasTitle = true;
            }
            if (in_array($column, ['value', 'amount'])) {
                $hasValue = true;
            }
        }

        if (!$hasTitle) {
            return [
                'valid' => false,
                'message' => 'CSV must contain a "Title" or "Deal Title" column',
            ];
        }

        if (!$hasValue) {
            return [
                'valid' => false,
                'message' => 'CSV must contain a "Value" or "Amount" column',
            ];
        }

        return [
            'valid' => true,
            'message' => 'CSV structure is valid',
        ];
    }

    /**
     * Generate CSV template for download
     */
    public function getCsvTemplate(): string
    {
        $headers = [
            'Title',
            'Description',
            'Contact Name',
            'Contact Email',
            'Stage',
            'Value',
            'Currency',
            'Probability',
            'Expected Close Date',
            'Actual Close Date',
            'Source',
            'Priority',
            'Loss Reason',
            'Notes',
        ];

        $exampleRow = [
            'Website Redesign Project',
            'Complete redesign of company website with modern UI',
            'John Doe',
            'john@example.com',
            'proposal',
            '15000',
            'USD',
            '60',
            '2025-12-31',
            '',
            'Website',
            'high',
            '',
            'Client interested in modern, responsive design',
        ];

        $csv = implode(',', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";
        $csv .= implode(',', array_map(fn($v) => '"' . $v . '"', $exampleRow)) . "\n";

        return $csv;
    }
}
