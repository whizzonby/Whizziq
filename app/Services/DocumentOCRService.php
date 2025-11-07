<?php

namespace App\Services;

use App\Models\TaxDocument;
use Illuminate\Support\Facades\{Storage, Http, Log};

/**
 * Document OCR Service
 *
 * Integrates with OCR providers to extract data from tax documents
 * Supported providers: Google Cloud Vision, AWS Textract, Azure Computer Vision
 */
class DocumentOCRService
{
    /**
     * Process document with OCR
     */
    public function processDocument(TaxDocument $document): array
    {
        try {
            $filePath = Storage::path($document->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception('Document file not found');
            }

            // Determine OCR provider from config
            $provider = config('services.ocr.provider', 'google');

            $extractedData = match($provider) {
                'google' => $this->processWithGoogleVision($filePath, $document),
                'aws' => $this->processWithAWSTextract($filePath, $document),
                'azure' => $this->processWithAzure($filePath, $document),
                default => $this->processWithBasicExtraction($filePath, $document),
            };

            // Update document with extracted data
            $document->update([
                'extracted_data' => $extractedData,
                'ocr_processed' => true,
                'ocr_processed_at' => now(),
            ]);

            // Auto-fill known fields
            $this->autoFillDocumentFields($document, $extractedData);

            return [
                'success' => true,
                'data' => $extractedData,
            ];

        } catch (\Exception $e) {
            Log::error('OCR processing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Queue document for background processing
     */
    public function queueDocumentForProcessing(TaxDocument $document): void
    {
        // This would dispatch a job for background processing
        // For now, process synchronously
        $this->processDocument($document);
    }

    /**
     * Process with Google Cloud Vision
     */
    protected function processWithGoogleVision(string $filePath, TaxDocument $document): array
    {
        $apiKey = config('services.ocr.google_api_key');

        if (!$apiKey) {
            return $this->processWithBasicExtraction($filePath, $document);
        }

        try {
            $imageData = base64_encode(file_get_contents($filePath));

            $response = Http::post('https://vision.googleapis.com/v1/images:annotate?key=' . $apiKey, [
                'requests' => [
                    [
                        'image' => ['content' => $imageData],
                        'features' => [
                            ['type' => 'TEXT_DETECTION'],
                            ['type' => 'DOCUMENT_TEXT_DETECTION'],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['responses'][0]['fullTextAnnotation']['text'] ?? '';

                return $this->extractTaxDataFromText($text, $document);
            }

        } catch (\Exception $e) {
            Log::warning('Google Vision API failed', ['error' => $e->getMessage()]);
        }

        return $this->processWithBasicExtraction($filePath, $document);
    }

    /**
     * Process with AWS Textract
     */
    protected function processWithAWSTextract(string $filePath, TaxDocument $document): array
    {
        // AWS Textract integration would go here
        // For now, fallback to basic extraction
        return $this->processWithBasicExtraction($filePath, $document);
    }

    /**
     * Process with Azure Computer Vision
     */
    protected function processWithAzure(string $filePath, TaxDocument $document): array
    {
        // Azure integration would go here
        // For now, fallback to basic extraction
        return $this->processWithBasicExtraction($filePath, $document);
    }

    /**
     * Basic extraction without OCR (extracts metadata only)
     */
    protected function processWithBasicExtraction(string $filePath, TaxDocument $document): array
    {
        return [
            'file_name' => basename($filePath),
            'file_size' => filesize($filePath),
            'file_type' => mime_content_type($filePath),
            'upload_date' => now()->toISOString(),
            'ocr_method' => 'basic_metadata_only',
            'note' => 'OCR service not configured. Please set up Google Vision, AWS Textract, or Azure Computer Vision for automatic data extraction.',
        ];
    }

    /**
     * Extract tax-specific data from OCR text
     */
    protected function extractTaxDataFromText(string $text, TaxDocument $document): array
    {
        $extracted = [
            'raw_text' => $text,
            'ocr_method' => 'google_vision',
        ];

        // Extract based on document type
        switch ($document->document_type) {
            case 'w2':
                $extracted = array_merge($extracted, $this->extractW2Data($text));
                break;

            case '1099_nec':
            case '1099_misc':
                $extracted = array_merge($extracted, $this->extract1099Data($text));
                break;

            case 'receipt':
            case 'invoice':
                $extracted = array_merge($extracted, $this->extractReceiptData($text));
                break;
        }

        return $extracted;
    }

    /**
     * Extract W-2 specific data
     */
    protected function extractW2Data(string $text): array
    {
        $data = [];

        // Extract employer name (common pattern: "Employer's name" or "payer")
        if (preg_match('/employer[\'s]*\s+name[:\s]+([^\n]+)/i', $text, $matches)) {
            $data['employer_name'] = trim($matches[1]);
        }

        // Extract wages (Box 1)
        if (preg_match('/wages.*?[\$]?\s*([\d,]+\.?\d*)/i', $text, $matches)) {
            $data['wages'] = (float) str_replace(',', '', $matches[1]);
        }

        // Extract federal tax withheld (Box 2)
        if (preg_match('/federal.*?withh.*?[\$]?\s*([\d,]+\.?\d*)/i', $text, $matches)) {
            $data['federal_withholding'] = (float) str_replace(',', '', $matches[1]);
        }

        // Extract EIN
        if (preg_match('/(\d{2}-\d{7})/', $text, $matches)) {
            $data['employer_ein'] = $matches[1];
        }

        return $data;
    }

    /**
     * Extract 1099 data
     */
    protected function extract1099Data(string $text): array
    {
        $data = [];

        // Extract payer name
        if (preg_match('/payer[\'s]*\s+name[:\s]+([^\n]+)/i', $text, $matches)) {
            $data['payer_name'] = trim($matches[1]);
        }

        // Extract nonemployee compensation
        if (preg_match('/nonemployee.*?compensation.*?[\$]?\s*([\d,]+\.?\d*)/i', $text, $matches)) {
            $data['amount'] = (float) str_replace(',', '', $matches[1]);
        }

        // Extract EIN
        if (preg_match('/(\d{2}-\d{7})/', $text, $matches)) {
            $data['payer_ein'] = $matches[1];
        }

        return $data;
    }

    /**
     * Extract receipt/invoice data
     */
    protected function extractReceiptData(string $text): array
    {
        $data = [];

        // Extract total amount
        if (preg_match('/total[:\s]+[\$]?\s*([\d,]+\.?\d*)/i', $text, $matches)) {
            $data['amount'] = (float) str_replace(',', '', $matches[1]);
        }

        // Extract date
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $text, $matches)) {
            $data['date'] = $matches[1];
        }

        // Extract vendor name (first line typically)
        $lines = explode("\n", $text);
        if (!empty($lines[0])) {
            $data['vendor_name'] = trim($lines[0]);
        }

        return $data;
    }

    /**
     * Auto-fill document fields from extracted data
     */
    protected function autoFillDocumentFields(TaxDocument $document, array $extractedData): void
    {
        $updates = [];

        // Auto-fill amount if found
        if (isset($extractedData['amount']) && !$document->amount) {
            $updates['amount'] = $extractedData['amount'];
        }

        // Auto-fill payer/vendor name
        if (isset($extractedData['payer_name']) && !$document->payer_name) {
            $updates['payer_name'] = $extractedData['payer_name'];
        } elseif (isset($extractedData['employer_name']) && !$document->payer_name) {
            $updates['payer_name'] = $extractedData['employer_name'];
        } elseif (isset($extractedData['vendor_name']) && !$document->payer_name) {
            $updates['payer_name'] = $extractedData['vendor_name'];
        }

        // Auto-fill EIN/TIN
        if (isset($extractedData['employer_ein']) && !$document->payer_tin) {
            $updates['payer_tin'] = $extractedData['employer_ein'];
        } elseif (isset($extractedData['payer_ein']) && !$document->payer_tin) {
            $updates['payer_tin'] = $extractedData['payer_ein'];
        }

        // Update document if we have data
        if (!empty($updates)) {
            $document->update($updates);
        }
    }
}
