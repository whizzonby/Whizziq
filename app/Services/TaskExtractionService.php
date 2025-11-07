<?php

namespace App\Services;

use App\Models\DocumentVault;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TaskExtractionService
{
    protected OpenAIService $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    /**
     * Extract action items from a document
     */
    public function extractFromDocument(DocumentVault $document): array
    {
        $content = $this->extractTextFromDocument($document);

        if (empty($content)) {
            return [];
        }

        return $this->extractActionItems($content, $document);
    }

    /**
     * Extract text content from document
     */
    protected function extractTextFromDocument(DocumentVault $document): string
    {
        $filePath = Storage::disk('public')->path($document->file_path);

        if (!file_exists($filePath)) {
            return '';
        }

        $extension = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));

        try {
            $text = match ($extension) {
                'pdf' => $this->extractFromPDF($filePath),
                'txt', 'md' => file_get_contents($filePath) ?: '',
                'docx' => $this->extractFromDocx($filePath),
                default => '',
            };
            
            // Sanitize all extracted text to ensure UTF-8 encoding
            return $this->sanitizeUtf8($text) ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract text from PDF
     */
    protected function extractFromPDF(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            return $this->sanitizeUtf8($text) ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract text from DOCX (basic implementation)
     */
    protected function extractFromDocx(string $filePath): string
    {
        // This is a simplified version - you might want to use a library like PHPWord
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();

                if ($xml) {
                    $xml = simplexml_load_string($xml);
                    $text = strip_tags($xml->asXML());
                    return $this->sanitizeUtf8($text) ?? '';
                }
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return '';
    }

    /**
     * Use AI to extract action items from text
     */
    protected function extractActionItems(string $content, DocumentVault $document): array
    {
        $systemPrompt = "You are an AI assistant that extracts actionable tasks from documents.
Analyze the following document and identify all action items, to-dos, and tasks that need to be done.

For each action item, provide:
1. A clear, concise title (max 100 characters)
2. A brief description if context is needed
3. Suggested priority (urgent, high, medium, or low)
4. Any mentioned deadline or due date
5. Estimated time if mentioned

IMPORTANT: You MUST return ONLY a valid JSON array. Do NOT include markdown code blocks, explanations, or any other text.
Return ONLY the JSON array starting with [ and ending with ].

Return ONLY a JSON array of tasks:
[
    {
        \"title\": \"Task title\",
        \"description\": \"Brief context\",
        \"priority\": \"high\",
        \"due_date\": \"2025-10-20\" or null,
        \"estimated_minutes\": 30 or null,
        \"notes\": \"Additional context\"
    }
]

If no action items are found, return an empty array [].";

        $userPrompt = "Document: {$document->file_name}\n\nContent:\n" . substr($content, 0, 8000); // Limit to avoid token limits

        try {
            $response = $this->openAI->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ], [
                'feature' => 'task_extraction',
                'max_tokens' => 2000,
            ]);

            if ($response === null) {
                // Check if it's a configuration issue
                $apiKey = config('services.openai.key');
                if (empty($apiKey)) {
                    Log::error('OpenAI API key not configured for document task extraction', [
                        'document_id' => $document->id,
                    ]);
                    return [];
                }
                
                Log::error('OpenAI API returned null response for document task extraction', [
                    'document_id' => $document->id,
                ]);
                return [];
            }

            // Extract JSON from response (handle markdown code blocks)
            $tasks = $this->extractJsonFromResponse($response);

            if (!is_array($tasks)) {
                Log::warning('Failed to parse document task extraction response as array', [
                    'document_id' => $document->id,
                    'response_preview' => substr($response, 0, 200),
                    'parsed_type' => gettype($tasks),
                ]);
                return [];
            }

            if (empty($tasks)) {
                Log::info('No tasks extracted from document', [
                    'document_id' => $document->id,
                ]);
                return [];
            }

            return array_map(function ($task) use ($document) {
                return [
                    'title' => $task['title'] ?? 'Untitled task',
                    'description' => $task['description'] ?? null,
                    'priority' => $this->validatePriority($task['priority'] ?? 'medium'),
                    'due_date' => $this->validateDate($task['due_date'] ?? null),
                    'estimated_minutes' => $task['estimated_minutes'] ?? null,
                    'notes' => $task['notes'] ?? null,
                    'source' => 'ai_extracted',
                    'linked_document_id' => $document->id,
                ];
            }, $tasks);
        } catch (\Exception $e) {
            Log::error('Document task extraction failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Create tasks from extracted action items
     */
    public function createTasksFromDocument(DocumentVault $document, User $user): array
    {
        $actionItems = $this->extractFromDocument($document);
        $createdTasks = [];

        foreach ($actionItems as $item) {
            $task = Task::create([
                'user_id' => $user->id,
                'title' => $item['title'],
                'description' => $item['description'],
                'priority' => $item['priority'],
                'due_date' => $item['due_date'],
                'estimated_minutes' => $item['estimated_minutes'],
                'notes' => $item['notes'],
                'source' => 'ai_extracted',
                'linked_document_id' => $document->id,
                'status' => 'pending',
            ]);

            $createdTasks[] = $task;
        }

        return $createdTasks;
    }

    /**
     * Extract action items from plain text
     */
    public function extractFromText(string $text, User $user): array
    {
        $systemPrompt = "You are an AI assistant that extracts actionable tasks from text.
The user might paste meeting notes, emails, or quick thoughts.

IMPORTANT: You MUST return ONLY a valid JSON array. Do NOT include markdown code blocks, explanations, or any other text.
Return ONLY the JSON array starting with [ and ending with ].

Extract all action items and return them as a JSON array:
[
    {
        \"title\": \"Task title\",
        \"description\": \"Brief context\",
        \"priority\": \"medium\",
        \"estimated_minutes\": 30 or null
    }
]

If no action items are found, return an empty array: []";

        try {
            $response = $this->openAI->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text],
            ], [
                'feature' => 'task_extraction',
                'max_tokens' => 2000,
            ]);

            if ($response === null) {
                // Check if it's a configuration issue
                $apiKey = config('services.openai.key');
                if (empty($apiKey)) {
                    Log::error('OpenAI API key not configured for task extraction', [
                        'user_id' => $user->id,
                    ]);
                    throw new \Exception('OpenAI API key is not configured. Please configure OPENAI_API_KEY in your .env file.');
                }
                
                Log::error('OpenAI API returned null response for task extraction', [
                    'user_id' => $user->id,
                    'text_preview' => substr($text, 0, 100),
                ]);
                throw new \Exception('AI service is currently unavailable. Please try again later.');
            }

            // Extract JSON from response (handle markdown code blocks)
            $tasks = $this->extractJsonFromResponse($response);

            if (!is_array($tasks)) {
                Log::warning('Failed to parse task extraction response as array', [
                    'user_id' => $user->id,
                    'response_preview' => substr($response, 0, 200),
                    'parsed_type' => gettype($tasks),
                ]);
                return [];
            }

            if (empty($tasks)) {
                Log::info('No tasks extracted from text', [
                    'user_id' => $user->id,
                    'text_preview' => substr($text, 0, 100),
                ]);
                return [];
            }

            $createdTasks = [];

            foreach ($tasks as $task) {
                if (!isset($task['title']) || empty($task['title'])) {
                    Log::warning('Skipping task with missing title', [
                        'task_data' => $task,
                    ]);
                    continue;
                }

                $createdTasks[] = Task::create([
                    'user_id' => $user->id,
                    'title' => $task['title'],
                    'description' => $task['description'] ?? null,
                    'priority' => $this->validatePriority($task['priority'] ?? 'medium'),
                    'estimated_minutes' => $task['estimated_minutes'] ?? null,
                    'source' => 'ai_extracted',
                    'status' => 'pending',
                ]);
            }

            Log::info('Tasks extracted successfully', [
                'user_id' => $user->id,
                'tasks_count' => count($createdTasks),
            ]);

            return $createdTasks;
        } catch (\Exception $e) {
            Log::error('Task extraction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to allow better error handling in the UI
            throw $e;
        }
    }

    /**
     * Validate priority value
     */
    protected function validatePriority(?string $priority): string
    {
        $valid = ['urgent', 'high', 'medium', 'low'];
        return in_array($priority, $valid) ? $priority : 'medium';
    }

    /**
     * Validate and parse date
     */
    protected function validateDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::parse($date);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract JSON from OpenAI response that might be wrapped in markdown code blocks
     */
    protected function extractJsonFromResponse(string $response): mixed
    {
        // Remove markdown code blocks if present
        $cleaned = preg_replace('/```(?:json)?\s*\n?/i', '', $response);
        $cleaned = preg_replace('/\n?```\s*$/i', '', $cleaned);
        $cleaned = trim($cleaned);

        // Try to find JSON array in the response
        // Look for array pattern
        if (preg_match('/\[[\s\S]*\]/', $cleaned, $matches)) {
            $jsonString = $matches[0];
        } else {
            $jsonString = $cleaned;
        }

        // Try to decode JSON
        $decoded = json_decode($jsonString, true);

        // If decoding failed, try to extract JSON more aggressively
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to find the first valid JSON array
            $start = strpos($cleaned, '[');
            $end = strrpos($cleaned, ']');

            if ($start !== false && $end !== false && $end > $start) {
                $jsonString = substr($cleaned, $start, $end - $start + 1);
                $decoded = json_decode($jsonString, true);
            }

            // If still failed, log the error
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('JSON parsing error in task extraction', [
                    'json_error' => json_last_error_msg(),
                    'response_preview' => substr($response, 0, 500),
                    'cleaned_preview' => substr($cleaned, 0, 500),
                ]);
            }
        }

        return $decoded;
    }

    /**
     * Sanitize text to ensure valid UTF-8 encoding
     * Removes or replaces invalid UTF-8 sequences
     */
    protected function sanitizeUtf8(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Ensure valid UTF-8 encoding
        // First, try to convert from detected encoding if it's not UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to detect encoding and convert
            $detected = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            
            if ($detected && $detected !== 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $detected);
            }
        }

        // Remove or replace invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remove any remaining control characters except newlines, tabs, and carriage returns
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Remove zero-width characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return trim($text);
    }
}
