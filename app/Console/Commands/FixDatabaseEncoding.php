<?php

namespace App\Console\Commands;

use App\Models\ClientInvoice;
use App\Models\InvoiceClient;
use App\Models\ClientInvoiceItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDatabaseEncoding extends Command
{
    protected $signature = 'fix:database-encoding {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix UTF-8 encoding issues in database data';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('Scanning database for encoding issues...');

        $fixedCount = 0;

        // Fix invoice clients
        $fixedCount += $this->fixInvoiceClients($dryRun);

        // Fix invoices
        $fixedCount += $this->fixInvoices($dryRun);

        // Fix invoice items
        $fixedCount += $this->fixInvoiceItems($dryRun);

        if ($dryRun) {
            $this->info("Would fix {$fixedCount} records with encoding issues");
        } else {
            $this->info("Fixed {$fixedCount} records with encoding issues");
        }

        return 0;
    }

    private function fixInvoiceClients(bool $dryRun): int
    {
        $this->info('Checking invoice clients...');
        $fixedCount = 0;

        $clients = InvoiceClient::all();
        
        foreach ($clients as $client) {
            $needsUpdate = false;
            $updateData = [];

            $stringFields = ['name', 'email', 'phone', 'company', 'address', 'city', 'state', 'zip', 'country', 'tax_id', 'notes'];
            
            foreach ($stringFields as $field) {
                $value = $client->$field;
                if ($value && !mb_check_encoding($value, 'UTF-8')) {
                    $cleanedValue = $this->cleanString($value);
                    if ($cleanedValue !== $value) {
                        $updateData[$field] = $cleanedValue;
                        $needsUpdate = true;
                        $this->warn("Client {$client->id} field '{$field}' has encoding issues");
                    }
                }
            }

            if ($needsUpdate) {
                if (!$dryRun) {
                    $client->update($updateData);
                }
                $fixedCount++;
            }
        }

        return $fixedCount;
    }

    private function fixInvoices(bool $dryRun): int
    {
        $this->info('Checking invoices...');
        $fixedCount = 0;

        $invoices = ClientInvoice::all();
        
        foreach ($invoices as $invoice) {
            $needsUpdate = false;
            $updateData = [];

            $stringFields = ['invoice_number', 'notes', 'terms', 'footer'];
            
            foreach ($stringFields as $field) {
                $value = $invoice->$field;
                if ($value && !mb_check_encoding($value, 'UTF-8')) {
                    $cleanedValue = $this->cleanString($value);
                    if ($cleanedValue !== $value) {
                        $updateData[$field] = $cleanedValue;
                        $needsUpdate = true;
                        $this->warn("Invoice {$invoice->id} field '{$field}' has encoding issues");
                    }
                }
            }

            if ($needsUpdate) {
                if (!$dryRun) {
                    $invoice->update($updateData);
                }
                $fixedCount++;
            }
        }

        return $fixedCount;
    }

    private function fixInvoiceItems(bool $dryRun): int
    {
        $this->info('Checking invoice items...');
        $fixedCount = 0;

        $items = ClientInvoiceItem::all();
        
        foreach ($items as $item) {
            $needsUpdate = false;
            $updateData = [];

            $stringFields = ['description', 'details'];
            
            foreach ($stringFields as $field) {
                $value = $item->$field;
                if ($value && !mb_check_encoding($value, 'UTF-8')) {
                    $cleanedValue = $this->cleanString($value);
                    if ($cleanedValue !== $value) {
                        $updateData[$field] = $cleanedValue;
                        $needsUpdate = true;
                        $this->warn("Invoice item {$item->id} field '{$field}' has encoding issues");
                    }
                }
            }

            if ($needsUpdate) {
                if (!$dryRun) {
                    $item->update($updateData);
                }
                $fixedCount++;
            }
        }

        return $fixedCount;
    }

    private function cleanString(string $string): string
    {
        // Remove null bytes and control characters
        $string = str_replace(["\0", "\r"], '', $string);

        // Normalize line endings
        $string = str_replace(["\r\n", "\r"], "\n", $string);

        // Remove excessive whitespace
        $string = preg_replace('/\s+/', ' ', $string);

        // More aggressive encoding detection and conversion
        $detectedEncoding = mb_detect_encoding($string, [
            'UTF-8', 'UTF-16', 'UTF-32', 'ISO-8859-1', 'Windows-1252', 'ASCII'
        ], true);

        if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
            $string = mb_convert_encoding($string, 'UTF-8', $detectedEncoding);
        }

        // Check if string is valid UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            // Try to convert from various encodings
            $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'];
            foreach ($encodings as $encoding) {
                if (mb_check_encoding($string, $encoding)) {
                    $string = mb_convert_encoding($string, 'UTF-8', $encoding);
                    break;
                }
            }
        }

        // Normalize Unicode characters
        if (class_exists('\Normalizer')) {
            $string = \Normalizer::normalize($string, \Normalizer::FORM_C);
        }

        // More comprehensive character replacement
        $string = str_replace([
            "\xE2\x80\x93", "\xE2\x80\x94", // en/em dash
            "\xE2\x80\x98", "\xE2\x80\x99", // smart single quotes
            "\xE2\x80\x9A", // single low quote
            "\xE2\x80\x9C", "\xE2\x80\x9D", // smart double quotes
            "\xE2\x80\x9E", // double low quote
            "\xE2\x80\xA6", // ellipsis
            "\xC2\xA0", // non-breaking space
            "\xE2\x80\x8B", // zero width space
            "\xE2\x80\x8C", // zero width non-joiner
            "\xE2\x80\x8D", // zero width joiner
        ], [
            '-', '-',
            "'", "'",
            ',',
            '"', '"',
            '"',
            '...',
            ' ',
            '', '', '',
        ], $string);

        // Remove control characters and null bytes
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);

        // Remove any remaining non-printable characters except newlines and tabs
        $string = preg_replace('/[^\x20-\x7E\x0A\x0D\x09]/', '', $string);

        // Trim whitespace
        return trim($string);
    }
}
