<?php

namespace App\Services;

use App\Models\DocumentVault;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentAnalysisService
{
    protected OpenAIService $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    /**
     * Analyze a document using AI
     */
    public function analyzeDocument(DocumentVault $document): array
    {
        try {
            // Extract text from document
            $text = $this->extractTextFromDocument($document);

            if (empty($text)) {
                return [
                    'success' => false,
                    'error' => 'Could not extract text from document',
                ];
            }

            // Truncate if text is too long (OpenAI has token limits)
            $text = substr($text, 0, 10000);

            // Generate comprehensive analysis
            $analysis = $this->generateAnalysis($document, $text);

            if (!$analysis) {
                return [
                    'success' => false,
                    'error' => 'AI analysis failed',
                ];
            }

            // Update document with analysis
            $document->update([
                'ai_summary' => $analysis['summary'] ?? null,
                'ai_key_points' => $analysis['key_points'] ?? null,
                'ai_analysis' => $analysis['detailed_analysis'] ?? null,
                'analyzed_at' => now(),
            ]);

            return [
                'success' => true,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('Document analysis failed', [
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
     * Extract text from document based on file type
     */
    protected function extractTextFromDocument(DocumentVault $document): ?string
    {
        try {
            if ($document->isPdf()) {
                return $this->extractTextFromPdf($document);
            }

            if ($document->file_type === 'txt') {
                return Storage::get($document->file_path);
            }

            // For other file types, we can't extract text easily
            // In a production app, you'd use libraries for DOCX, XLSX, etc.
            return null;
        } catch (\Exception $e) {
            Log::error('Text extraction failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract text from PDF using PDF parser
     */
    protected function extractTextFromPdf(DocumentVault $document): ?string
    {
        try {
            $parser = new PdfParser();
            $filePath = Storage::path($document->file_path);
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            return $text;
        } catch (\Exception $e) {
            Log::error('PDF text extraction failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate AI analysis based on document type and content
     */
    protected function generateAnalysis(DocumentVault $document, string $text): ?array
    {
        $category = $document->category ?? 'general';

        $systemPrompt = $this->getSystemPromptForCategory($category);
        $userPrompt = $this->buildAnalysisPrompt($document, $text, $category);

        $response = $this->openAI->chat([
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $userPrompt,
            ],
        ], [
            'max_tokens' => 2000,
            'temperature' => 0.3,
        ]);

        if (!$response) {
            return null;
        }

        return $this->parseAnalysisResponse($response, $category);
    }

    /**
     * Get system prompt based on document category
     */
    protected function getSystemPromptForCategory(string $category): string
    {
        return match ($category) {
            'legal' => 'You are a legal document analyst. Analyze legal documents and break them down into simple, easy-to-understand language. Highlight key terms, obligations, rights, deadlines, and potential risks. Explain legal jargon in plain English.',

            'financial' => 'You are a financial document analyst. Analyze financial documents and provide clear insights. Identify key financial metrics, trends, risks, and opportunities. Explain complex financial terms in simple language.',

            'contract' => 'You are a contract analyst. Review contracts and identify key clauses, obligations, rights, termination conditions, payment terms, and potential red flags. Simplify legal language for better understanding.',

            'medical' => 'You are a medical document analyst. Analyze medical documents and translate medical terminology into plain language. Highlight key diagnoses, treatments, medications, and important dates.',

            'business' => 'You are a business document analyst. Analyze business documents and extract key insights, action items, decisions, and strategic information. Provide actionable summaries.',

            'invoice' => 'You are an invoice analyst. Extract key billing information, amounts, due dates, payment terms, and verify calculations. Flag any discrepancies.',

            'report' => 'You are a report analyst. Summarize key findings, conclusions, recommendations, and data insights. Highlight important metrics and trends.',

            default => 'You are a document analyst. Analyze documents and provide clear, actionable insights. Break down complex information into simple, easy-to-understand summaries.',
        };
    }

    /**
     * Build analysis prompt
     */
    protected function buildAnalysisPrompt(DocumentVault $document, string $text, string $category): string
    {
        $prompt = "Document Title: {$document->title}\n";
        $prompt .= "Document Type: " . ucfirst($category) . "\n\n";
        $prompt .= "Document Content:\n{$text}\n\n";
        $prompt .= "Please provide:\n";
        $prompt .= "1. EXECUTIVE SUMMARY (2-3 sentences)\n";
        $prompt .= "2. KEY POINTS (5-7 bullet points of the most important information)\n";
        $prompt .= "3. DETAILED ANALYSIS:\n";

        $prompt .= match ($category) {
            'legal' => "   - Main legal provisions\n   - Your rights and obligations\n   - Important deadlines or dates\n   - Potential risks or concerns\n   - Legal terms explained\n   - Recommendations\n",

            'financial' => "   - Key financial figures\n   - Financial health indicators\n   - Trends and patterns\n   - Risks and opportunities\n   - Action items\n   - Recommendations\n",

            'contract' => "   - Contract parties\n   - Scope of agreement\n   - Payment terms\n   - Duration and termination\n   - Key obligations\n   - Important clauses\n   - Red flags or concerns\n",

            'medical' => "   - Patient information\n   - Diagnoses or findings\n   - Treatments or procedures\n   - Medications\n   - Important dates\n   - Follow-up actions\n",

            'business' => "   - Main topics covered\n   - Key decisions or conclusions\n   - Action items\n   - Stakeholders involved\n   - Timelines\n   - Strategic implications\n",

            default => "   - Main topics\n   - Key information\n   - Important points\n   - Action items (if any)\n   - Recommendations\n",
        };

        $prompt .= "\nFormat your response clearly with headers and bullet points.";

        return $prompt;
    }

    /**
     * Parse AI response into structured format
     */
    protected function parseAnalysisResponse(string $response, string $category): array
    {
        $sections = [
            'summary' => '',
            'key_points' => '',
            'detailed_analysis' => [],
        ];

        // Split response into sections
        $lines = explode("\n", $response);
        $currentSection = null;
        $currentSubsection = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Detect sections
            if (preg_match('/^(EXECUTIVE SUMMARY|SUMMARY)/i', $line)) {
                $currentSection = 'summary';
                continue;
            } elseif (preg_match('/^KEY POINTS/i', $line)) {
                $currentSection = 'key_points';
                continue;
            } elseif (preg_match('/^(DETAILED ANALYSIS|ANALYSIS)/i', $line)) {
                $currentSection = 'detailed';
                continue;
            }

            // Add content to appropriate section
            if ($currentSection === 'summary') {
                $sections['summary'] .= $line . ' ';
            } elseif ($currentSection === 'key_points') {
                $sections['key_points'] .= $line . "\n";
            } elseif ($currentSection === 'detailed') {
                // Check if it's a subsection header
                if (preg_match('/^[\-•]\s*(.+):\s*$/', $line, $matches)) {
                    $currentSubsection = $matches[1];
                    $sections['detailed_analysis'][$currentSubsection] = '';
                } elseif ($currentSubsection) {
                    $sections['detailed_analysis'][$currentSubsection] .= $line . "\n";
                } else {
                    // Generic analysis text
                    if (!isset($sections['detailed_analysis']['General'])) {
                        $sections['detailed_analysis']['General'] = '';
                    }
                    $sections['detailed_analysis']['General'] .= $line . "\n";
                }
            }
        }

        // Clean up
        $sections['summary'] = trim($sections['summary']);
        $sections['key_points'] = trim($sections['key_points']);

        return $sections;
    }

    /**
     * Generate quick summary (faster, less detailed)
     */
    public function generateQuickSummary(DocumentVault $document): ?string
    {
        $text = $this->extractTextFromDocument($document);

        if (empty($text)) {
            return null;
        }

        $text = substr($text, 0, 5000);

        return $this->openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a document summarizer. Provide concise, clear summaries in 2-3 sentences.',
            ],
            [
                'role' => 'user',
                'content' => "Summarize this document:\n\n{$text}",
            ],
        ], [
            'max_tokens' => 200,
        ]);
    }
}
