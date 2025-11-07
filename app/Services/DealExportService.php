<?php

namespace App\Services;

use App\Models\Deal;
use Illuminate\Support\Collection;

class DealExportService
{
    /**
     * Export deals to CSV
     */
    public function exportToCsv(int $userId, ?array $filters = null): string
    {
        $query = Deal::with('contact')->where('user_id', $userId);

        // Apply filters
        if ($filters) {
            if (isset($filters['stage'])) {
                $query->where('stage', $filters['stage']);
            }

            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (isset($filters['status'])) {
                if ($filters['status'] === 'open') {
                    $query->open();
                } elseif ($filters['status'] === 'closed') {
                    $query->closed();
                } elseif ($filters['status'] === 'won') {
                    $query->won();
                } elseif ($filters['status'] === 'lost') {
                    $query->lost();
                }
            }
        }

        $deals = $query->orderBy('created_at', 'desc')->get();

        return $this->generateCsv($deals);
    }

    /**
     * Generate CSV content from deals collection
     */
    protected function generateCsv(Collection $deals): string
    {
        $headers = [
            'ID',
            'Title',
            'Description',
            'Contact Name',
            'Contact Email',
            'Contact Phone',
            'Contact Company',
            'Stage',
            'Value',
            'Currency',
            'Probability',
            'Weighted Value',
            'Expected Close Date',
            'Actual Close Date',
            'Source',
            'Priority',
            'Loss Reason',
            'Days in Stage',
            'Days Until Expected Close',
            'Notes',
            'Created At',
            'Last Updated',
        ];

        // Start CSV with headers
        $csv = $this->arrayToCsvRow($headers);

        // Add each deal as a row
        foreach ($deals as $deal) {
            $row = [
                $deal->id,
                $deal->title,
                $deal->description ?? '',
                $deal->contact?->name ?? '',
                $deal->contact?->email ?? '',
                $deal->contact?->phone ?? '',
                $deal->contact?->company ?? '',
                $deal->stage_label,
                $deal->value,
                $deal->currency,
                $deal->probability,
                $deal->weighted_value,
                $deal->expected_close_date?->format('Y-m-d') ?? '',
                $deal->actual_close_date?->format('Y-m-d') ?? '',
                $deal->source ?? '',
                $deal->priority,
                $deal->loss_reason ?? '',
                $deal->days_in_stage,
                $deal->days_until_expected_close ?? '',
                $deal->notes ?? '',
                $deal->created_at->format('Y-m-d H:i:s'),
                $deal->updated_at->format('Y-m-d H:i:s'),
            ];

            $csv .= $this->arrayToCsvRow($row);
        }

        return $csv;
    }

    /**
     * Convert array to CSV row
     */
    protected function arrayToCsvRow(array $data): string
    {
        $escaped = array_map(function($value) {
            // Escape quotes and wrap in quotes if contains comma, quote, or newline
            $value = str_replace('"', '""', $value);
            if (preg_match('/[",\n\r]/', $value)) {
                return '"' . $value . '"';
            }
            return $value;
        }, $data);

        return implode(',', $escaped) . "\n";
    }

    /**
     * Get export filename with timestamp
     */
    public function getExportFilename(): string
    {
        return 'deals_export_' . now()->format('Y-m-d_His') . '.csv';
    }
}
