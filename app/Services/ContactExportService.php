<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;

class ContactExportService
{
    /**
     * Export contacts to CSV
     */
    public function exportToCsv(int $userId, ?array $filters = null): string
    {
        $query = Contact::where('user_id', $userId);

        // Apply filters if provided
        if ($filters) {
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }
            if (isset($filters['relationship_strength'])) {
                $query->where('relationship_strength', $filters['relationship_strength']);
            }
        }

        $contacts = $query->orderBy('name')->get();

        return $this->generateCsv($contacts);
    }

    /**
     * Generate CSV content from contacts
     */
    protected function generateCsv(Collection $contacts): string
    {
        $csv = [];

        // Header row
        $csv[] = [
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
            'LinkedIn URL',
            'Twitter Handle',
            'Last Contact Date',
            'Next Follow-Up Date',
            'Relationship Strength',
            'Lifetime Value',
            'Deals Count',
            'Interactions Count',
            'Tags',
            'Notes',
            'Source',
            'Created At',
        ];

        // Data rows
        foreach ($contacts as $contact) {
            $csv[] = [
                $contact->name,
                $contact->email,
                $contact->phone,
                $contact->company,
                $contact->job_title,
                $contact->type,
                $contact->status,
                $contact->priority,
                $contact->address,
                $contact->city,
                $contact->state,
                $contact->zip,
                $contact->country,
                $contact->website,
                $contact->linkedin_url,
                $contact->twitter_handle,
                $contact->last_contact_date?->format('Y-m-d'),
                $contact->next_follow_up_date?->format('Y-m-d'),
                $contact->relationship_strength,
                $contact->lifetime_value,
                $contact->deals_count,
                $contact->interactions_count,
                is_array($contact->tags) ? implode('; ', $contact->tags) : '',
                $contact->notes,
                $contact->source,
                $contact->created_at?->format('Y-m-d H:i:s'),
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
     * Get filename for export
     */
    public function getExportFilename(): string
    {
        return 'contacts_export_' . date('Y-m-d_His') . '.csv';
    }
}
