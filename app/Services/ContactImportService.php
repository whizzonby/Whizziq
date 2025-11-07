<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ContactImportService
{
    /**
     * Import contacts from CSV data
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

                $contactData = array_combine($header, $data);

                // Map CSV columns to database columns
                $mappedData = $this->mapCsvToModel($contactData, $userId);

                // Validate
                $validator = Validator::make($mappedData, [
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:255',
                    'type' => 'required|in:client,lead,partner,investor,vendor,other',
                    'status' => 'required|in:active,inactive,archived',
                ]);

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create or update contact
                $contact = Contact::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'email' => $mappedData['email'],
                    ],
                    $mappedData
                );

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                Log::error("Contact import error", ['row' => $index + 2, 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Map CSV columns to model attributes
     */
    protected function mapCsvToModel(array $csvData, int $userId): array
    {
        $mapped = [
            'user_id' => $userId,
            'name' => $csvData['name'] ?? $csvData['full name'] ?? '',
            'email' => $csvData['email'] ?? $csvData['email address'] ?? null,
            'phone' => $csvData['phone'] ?? $csvData['phone number'] ?? null,
            'company' => $csvData['company'] ?? $csvData['organization'] ?? null,
            'job_title' => $csvData['job title'] ?? $csvData['title'] ?? $csvData['position'] ?? null,
            'type' => strtolower($csvData['type'] ?? $csvData['contact type'] ?? 'lead'),
            'status' => strtolower($csvData['status'] ?? 'active'),
            'priority' => strtolower($csvData['priority'] ?? 'medium'),
            'address' => $csvData['address'] ?? $csvData['street address'] ?? null,
            'city' => $csvData['city'] ?? null,
            'state' => $csvData['state'] ?? $csvData['province'] ?? null,
            'zip' => $csvData['zip'] ?? $csvData['postal code'] ?? $csvData['zipcode'] ?? null,
            'country' => $csvData['country'] ?? 'USA',
            'website' => $csvData['website'] ?? $csvData['web'] ?? null,
            'linkedin_url' => $csvData['linkedin'] ?? $csvData['linkedin url'] ?? null,
            'twitter_handle' => $csvData['twitter'] ?? $csvData['twitter handle'] ?? null,
            'notes' => $csvData['notes'] ?? null,
            'source' => $csvData['source'] ?? $csvData['lead source'] ?? null,
            'relationship_strength' => strtolower($csvData['relationship'] ?? $csvData['relationship strength'] ?? 'warm'),
        ];

        // Clean up empty values
        return array_filter($mapped, function($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Get CSV template
     */
    public function getCsvTemplate(): string
    {
        $headers = [
            'Name',
            'Email',
            'Phone',
            'Company',
            'Job Title',
            'Type',
            'Status',
            'Priority',
            'Address',
            'City',
            'State',
            'Zip',
            'Country',
            'Website',
            'LinkedIn',
            'Twitter',
            'Notes',
            'Source',
            'Relationship',
        ];

        $example = [
            'John Doe',
            'john@example.com',
            '+1-555-0100',
            'Acme Corp',
            'CEO',
            'client',
            'active',
            'high',
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'USA',
            'https://acmecorp.com',
            'https://linkedin.com/in/johndoe',
            '@johndoe',
            'Met at conference',
            'referral',
            'hot',
        ];

        return implode(',', $headers) . "\n" . implode(',', array_map(function($field) {
            return '"' . $field . '"';
        }, $example));
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
        $requiredColumns = ['name'];

        $headerLower = array_map('strtolower', array_map('trim', $header));

        foreach ($requiredColumns as $required) {
            if (!in_array($required, $headerLower)) {
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
