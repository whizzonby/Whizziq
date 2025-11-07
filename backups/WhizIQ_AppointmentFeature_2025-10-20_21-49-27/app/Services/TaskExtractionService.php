<?php

namespace App\Services;

use App\Models\DocumentVault;
use App\Models\Task;
use App\Models\User;
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
        $filePath = Storage::disk('private')->path($document->file_path);

        if (!file_exists($filePath)) {
            return '';
        }

        $extension = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));

        try {
            return match ($extension) {
                'pdf' => $this->extractFromPDF($filePath),
                'txt', 'md' => file_get_contents($filePath),
                'docx' => $this->extractFromDocx($filePath),
                default => '',
            };
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
            return $pdf->getText();
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
                    return strip_tags($xml->asXML());
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
            ]);

            // Parse JSON response
            $tasks = json_decode($response, true);

            if (!is_array($tasks)) {
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

Extract all action items and return them as a JSON array:
[
    {
        \"title\": \"Task title\",
        \"description\": \"Brief context\",
        \"priority\": \"medium\",
        \"estimated_minutes\": 30 or null
    }
]";

        try {
            $response = $this->openAI->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text],
            ]);

            $tasks = json_decode($response, true);

            if (!is_array($tasks)) {
                return [];
            }

            $createdTasks = [];

            foreach ($tasks as $task) {
                $createdTasks[] = Task::create([
                    'user_id' => $user->id,
                    'title' => $task['title'] ?? 'Untitled task',
                    'description' => $task['description'] ?? null,
                    'priority' => $this->validatePriority($task['priority'] ?? 'medium'),
                    'estimated_minutes' => $task['estimated_minutes'] ?? null,
                    'source' => 'ai_extracted',
                    'status' => 'pending',
                ]);
            }

            return $createdTasks;
        } catch (\Exception $e) {
            return [];
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
}
