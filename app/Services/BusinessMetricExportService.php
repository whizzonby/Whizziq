<?php

namespace App\Services;

use App\Models\BusinessMetric;
use Illuminate\Support\Collection;

class BusinessMetricExportService
{
    /**
     * Export business metrics to CSV
     */
    public function exportToCsv(int $userId, ?array $filters = null): string
    {
        $query = BusinessMetric::where('user_id', $userId);

        // Apply filters if provided
        if ($filters) {
            if (isset($filters['from_date'])) {
                $query->whereDate('date', '>=', $filters['from_date']);
            }
            if (isset($filters['until_date'])) {
                $query->whereDate('date', '<=', $filters['until_date']);
            }
        }

        $metrics = $query->orderBy('date', 'desc')->get();

        return $this->generateCsv($metrics);
    }

    /**
     * Generate CSV content from business metrics
     */
    protected function generateCsv(Collection $metrics): string
    {
        $csv = [];

        // Header row
        $csv[] = [
            'Date',
            'Revenue',
            'Profit',
            'Expenses',
            'Cash Flow',
            'Revenue Change %',
            'Profit Change %',
            'Expenses Change %',
            'Cash Flow Change %',
            'Created At',
        ];

        // Data rows
        foreach ($metrics as $metric) {
            $csv[] = [
                $metric->date->format('Y-m-d'),
                number_format($metric->revenue, 2, '.', ''),
                number_format($metric->profit, 2, '.', ''),
                number_format($metric->expenses, 2, '.', ''),
                number_format($metric->cash_flow, 2, '.', ''),
                $metric->revenue_change_percentage ? number_format($metric->revenue_change_percentage, 2, '.', '') : '',
                $metric->profit_change_percentage ? number_format($metric->profit_change_percentage, 2, '.', '') : '',
                $metric->expenses_change_percentage ? number_format($metric->expenses_change_percentage, 2, '.', '') : '',
                $metric->cash_flow_change_percentage ? number_format($metric->cash_flow_change_percentage, 2, '.', '') : '',
                $metric->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Export business metrics to Excel (CSV format for now, can be extended with PhpSpreadsheet)
     */
    public function exportToExcel(int $userId, ?array $filters = null): string
    {
        // For now, return CSV (can be extended to use PhpSpreadsheet for real Excel)
        return $this->exportToCsv($userId, $filters);
    }

    /**
     * Get filename for export
     */
    public function getExportFilename(string $format = 'csv'): string
    {
        $extension = $format === 'excel' ? 'xlsx' : 'csv';
        return 'business_metrics_export_' . date('Y-m-d_His') . '.' . $extension;
    }
}

