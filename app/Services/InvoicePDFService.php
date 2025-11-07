<?php

namespace App\Services;

use App\Models\ClientInvoice;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoicePDFService
{
    /**
     * Generate PDF for an invoice using Browsershot (Headless Chrome)
     *
     * @param ClientInvoice $invoice
     * @param bool $save Save to storage
     * @return string Binary PDF content
     * @throws \Exception
     */
    public function generate(ClientInvoice $invoice, bool $save = false)
    {
        try {
            // Load relationships only if invoice is persisted
            // For temporary/preview invoices, relationships are already manually set
            if ($invoice->exists && $invoice->id > 0) {
                $invoice->load(['client', 'items', 'user']);
            }

            // Validate required data
            if (!$invoice->client) {
                throw new \Exception('Client information is missing for this invoice.');
            }

            if (!$invoice->user) {
                throw new \Exception('User information is missing for this invoice.');
            }

            if (!$invoice->items || $invoice->items->isEmpty()) {
                throw new \Exception('Invoice must have at least one item.');
            }

            // Get template and color preferences from invoice or use defaults
            $template = $invoice->template ?? 'modern';
            $primaryColor = $invoice->primary_color ?? $this->getDefaultPrimaryColor($template);
            $accentColor = $invoice->accent_color ?? $this->getDefaultAccentColor($template);

            // Prepare data array for the Tailwind preview component (same as live preview)
            $data = [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'currency' => $invoice->currency,
                'tax_rate' => $invoice->tax_rate ?? 0,
                'discount_amount' => $invoice->discount_amount ?? 0,
                'items' => $invoice->items->toArray(),
                'invoice_client_id' => $invoice->invoice_client_id,
                'notes' => $invoice->notes,
                'terms' => $invoice->terms,
                'footer' => $invoice->footer,
            ];

            // Render the Tailwind preview component (same as live preview)
            $invoiceHtml = view('filament.dashboard.components.invoice-preview', [
                'data' => $data,
                'template' => $template,
                'primaryColor' => $primaryColor,
                'accentColor' => $accentColor,
            ])->render();

            // Wrap in full HTML document with Tailwind CDN
            $fullHtml = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice ' . $invoice->invoice_number . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-50 p-8">
    ' . $invoiceHtml . '
</body>
</html>';

            // Generate PDF using Browsershot
            $pdfContent = Browsershot::html($fullHtml)
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->timeout(120) // 2 minutes timeout for PDF generation
                ->pdf();

            // Save to storage if requested
            if ($save) {
                $this->savePDFContent($invoice, $pdfContent);
            }

            return $pdfContent;
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Sanitize invoice data
     */
    protected function sanitizeInvoiceData($invoice)
    {
        // Clean only string attributes, preserve dates and other types
        $attributes = $invoice->getAttributes();

        foreach ($attributes as $key => $value) {
            if (is_string($value) && !in_array($key, ['invoice_date', 'due_date', 'paid_date', 'created_at', 'updated_at'])) {
                $invoice->$key = $this->cleanString($value);
            }
        }

        return $invoice;
    }

    /**
     * Sanitize client data
     */
    protected function sanitizeClientData($client)
    {
        // Clean only string attributes, preserve dates and other types
        $attributes = $client->getAttributes();

        foreach ($attributes as $key => $value) {
            if (is_string($value) && !in_array($key, ['created_at', 'updated_at'])) {
                $client->$key = $this->cleanString($value);
            }
        }

        return $client;
    }

    /**
     * Sanitize items data
     */
    protected function sanitizeItemsData($items)
    {
        return $items->map(function ($item) {
            // Clean only string attributes
            $attributes = $item->getAttributes();

            foreach ($attributes as $key => $value) {
                if (is_string($value) && !in_array($key, ['created_at', 'updated_at'])) {
                    $item->$key = $this->cleanString($value);
                }
            }

            return $item;
        });
    }

    /**
     * Sanitize data to prevent UTF-8 encoding issues
     *
     * @param mixed $data
     * @return mixed
     */
    protected function sanitizeData($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        }

        if (is_object($data)) {
            // Don't sanitize Eloquent models
            return $data;
        }

        if (is_string($data)) {
            // Fix any malformed UTF-8 characters
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Remove null bytes and other control characters
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
        }

        return $data;
    }

    /**
     * Generate and download PDF
     *
     * @param ClientInvoice $invoice
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(ClientInvoice $invoice)
    {
        $pdfContent = $this->generate($invoice);
        $filename = $this->getFilename($invoice);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Generate and stream PDF (view in browser)
     *
     * @param ClientInvoice $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stream(ClientInvoice $invoice)
    {
        $pdfContent = $this->generate($invoice);
        $filename = $this->getFilename($invoice);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Save PDF binary content to storage
     *
     * @param ClientInvoice $invoice
     * @param string $pdfContent Binary PDF content from Browsershot
     * @return string Path to saved file
     */
    protected function savePDFContent(ClientInvoice $invoice, string $pdfContent): string
    {
        $filename = $this->getFilename($invoice);
        $path = "invoices/{$invoice->user_id}/{$filename}";

        // Save to storage
        Storage::put($path, $pdfContent);

        // Update invoice with PDF path (only for persisted invoices)
        if ($invoice->exists && $invoice->id > 0) {
            $invoice->update(['pdf_path' => $path]);
        }

        Log::info('Invoice PDF saved', [
            'invoice_id' => $invoice->id,
            'path' => $path,
        ]);

        return $path;
    }

    /**
     * Get PDF file from storage
     *
     * @param ClientInvoice $invoice
     * @return string|null
     */
    public function getStoredPDF(ClientInvoice $invoice): ?string
    {
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return Storage::get($invoice->pdf_path);
        }

        return null;
    }

    /**
     * Email invoice to client
     *
     * @param ClientInvoice $invoice
     * @param string|null $message Custom message
     * @return bool
     */
    public function emailToClient(ClientInvoice $invoice, ?string $message = null): bool
    {
        try {
            // Validate client email
            if (!$invoice->client || !$invoice->client->email) {
                Log::error('Cannot email invoice - missing client email', [
                    'invoice_id' => $invoice->id,
                    'client_id' => $invoice->client?->id
                ]);
                return false;
            }

            // Validate email format
            if (!filter_var($invoice->client->email, FILTER_VALIDATE_EMAIL)) {
                Log::error('Cannot email invoice - invalid client email format', [
                    'invoice_id' => $invoice->id,
                    'client_email' => $invoice->client->email
                ]);
                return false;
            }

            $pdfContent = $this->generate($invoice, true);
            $filename = $this->getFilename($invoice);

            // Default email message
            if (!$message) {
                $message = "Please find attached invoice {$invoice->invoice_number} for the amount of {$invoice->currency} " . number_format($invoice->total_amount, 2);
            }

            // Send email
            \Mail::send('invoices.email', [
                'invoice' => $invoice,
                'client' => $invoice->client,
                'emailMessage' => $message,
            ], function ($mail) use ($invoice, $pdfContent, $filename) {
                $mail->to($invoice->client->email, $invoice->client->name)
                    ->subject("Invoice {$invoice->invoice_number} from {$invoice->user->name}")
                    ->attachData($pdfContent, $filename, [
                        'mime' => 'application/pdf',
                    ]);
            });

            // Mark invoice as sent if it was draft
            if ($invoice->status === 'draft') {
                $invoice->markAsSent();
            }

            Log::info('Invoice emailed to client', [
                'invoice_id' => $invoice->id,
                'client_email' => $invoice->client->email,
            ]);

            return true;
        } catch (\Illuminate\Mail\Exception $e) {
            Log::error('Mail service error when sending invoice', [
                'invoice_id' => $invoice->id,
                'client_email' => $invoice->client?->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to email invoice', [
                'invoice_id' => $invoice->id,
                'client_email' => $invoice->client?->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get filename for PDF
     *
     * @param ClientInvoice $invoice
     * @return string
     */
    protected function getFilename(ClientInvoice $invoice): string
    {
        return "invoice-{$invoice->invoice_number}.pdf";
    }

    /**
     * Get company information for invoice
     *
     * @param \App\Models\User $user
     * @return array
     */
    protected function getCompanyInfo($user): array
    {
        // Try to get from business profile
        $businessProfile = null;
        try {
            $businessProfile = $user->businessProfile;
        } catch (\Exception $e) {
            // Relationship doesn't exist or error loading it
            Log::debug('BusinessProfile relationship not available', ['user_id' => $user->id]);
        }

        return [
            'name' => $this->cleanString($businessProfile?->biz_registered_name ?? $businessProfile?->biz_trading_name ?? $user->name ?? 'Company Name'),
            'email' => $this->cleanString($user->email ?? ''),
            'phone' => $this->cleanString($user->phone_number ?? ''),
            'address' => $this->cleanString($businessProfile?->ops_location ?? ''),
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => $this->cleanString($businessProfile?->biz_country ?? 'USA'),
            'tax_id' => $this->cleanString($businessProfile?->biz_tax_id ?? ''),
            'website' => '',
            'logo' => null, // Can be added later
        ];
    }

    /**
     * Clean string for PDF rendering
     *
     * @param string|null $string
     * @return string
     */
    protected function cleanString(?string $string): string
    {
        if ($string === null || $string === '') {
            return '';
        }

        // Convert to UTF-8 if not already
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string, mb_detect_order(), true) ?: 'UTF-8');
        }

        // Fix any remaining malformed UTF-8
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Replace smart quotes and special characters
        $string = str_replace([
            "\xE2\x80\x93", "\xE2\x80\x94", // en/em dash
            "\xE2\x80\x98", "\xE2\x80\x99", // smart single quotes
            "\xE2\x80\x9A", // single low quote
            "\xE2\x80\x9C", "\xE2\x80\x9D", // smart double quotes
            "\xE2\x80\x9E", // double low quote
            "\xE2\x80\xA6", // ellipsis
            "\xC2\xA0", // non-breaking space
        ], [
            '-', '-',
            "'", "'",
            ',',
            '"', '"',
            '"',
            '...',
            ' ',
        ], $string);

        // Remove control characters and null bytes
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);

        // Trim whitespace
        return trim($string);
    }

    /**
     * Generate invoice preview (HTML)
     *
     * @param ClientInvoice $invoice
     * @return string
     */
    public function generatePreview(ClientInvoice $invoice): string
    {
        $invoice->load(['client', 'items', 'user']);

        $data = [
            'invoice' => $invoice,
            'user' => $invoice->user,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'companyInfo' => $this->getCompanyInfo($invoice->user),
        ];

        return view('invoices.pdf', $data)->render();
    }

    /**
     * Bulk generate PDFs for multiple invoices
     *
     * @param array $invoiceIds
     * @return int Number of PDFs generated
     */
    public function bulkGenerate(array $invoiceIds): int
    {
        $count = 0;

        foreach ($invoiceIds as $invoiceId) {
            try {
                $invoice = ClientInvoice::find($invoiceId);
                if ($invoice) {
                    $this->generate($invoice, true);
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate invoice PDF', [
                    'invoice_id' => $invoiceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Delete stored PDF
     *
     * @param ClientInvoice $invoice
     * @return bool
     */
    public function deletePDF(ClientInvoice $invoice): bool
    {
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            Storage::delete($invoice->pdf_path);
            $invoice->update(['pdf_path' => null]);
            return true;
        }

        return false;
    }

    /**
     * Get default primary color for template
     *
     * @param string $template
     * @return string
     */
    protected function getDefaultPrimaryColor(string $template): string
    {
        return match($template) {
            'modern' => '#3b82f6',
            'elegant' => '#9333ea',
            'minimal' => '#64748b',
            'vibrant' => '#10b981',
            default => '#3b82f6',
        };
    }

    /**
     * Get default accent color for template
     *
     * @param string $template
     * @return string
     */
    protected function getDefaultAccentColor(string $template): string
    {
        return match($template) {
            'modern' => '#60a5fa',
            'elegant' => '#a855f7',
            'minimal' => '#94a3b8',
            'vibrant' => '#34d399',
            default => '#60a5fa',
        };
    }
}
