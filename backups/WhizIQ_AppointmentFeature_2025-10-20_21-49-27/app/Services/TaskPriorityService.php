<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;

class TaskPriorityService
{
    protected OpenAIService $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    /**
     * Calculate AI-powered priority score for a task
     */
    public function calculatePriorityScore(Task $task): array
    {
        $context = $this->buildTaskContext($task);

        $systemPrompt = "You are a task prioritization AI assistant for busy CEOs and entrepreneurs.
Your job is to analyze tasks and assign priority scores from 1-100 based on:
1. Urgency (deadline proximity)
2. Impact (business/strategic importance)
3. Dependencies (blocking other work)
4. Effort (time investment required)
5. Opportunity cost (what gets delayed if not done)

Return ONLY a JSON object with:
{
    \"score\": <number 1-100>,
    \"reasoning\": \"<2-3 sentence explanation>\",
    \"suggested_priority\": \"<urgent|high|medium|low>\"
}";

        $userPrompt = "Analyze this task and provide a priority score:\n\n" . $context;

        try {
            $response = $this->openAI->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ]);

            // Parse JSON response
            $result = json_decode($response, true);

            if (!$result || !isset($result['score'])) {
                // Fallback to rule-based scoring
                return $this->ruleBasedPriorityScore($task);
            }

            return [
                'score' => min(100, max(1, (int) $result['score'])),
                'reasoning' => $result['reasoning'] ?? 'AI analysis completed',
                'suggested_priority' => $result['suggested_priority'] ?? $this->mapScoreToPriority($result['score']),
            ];
        } catch (\Exception $e) {
            // Fallback to rule-based scoring
            return $this->ruleBasedPriorityScore($task);
        }
    }

    /**
     * Build context for AI analysis
     */
    protected function buildTaskContext(Task $task): string
    {
        $context = "Task: {$task->title}\n";

        if ($task->description) {
            $context .= "Description: {$task->description}\n";
        }

        $context .= "Current Priority: {$task->priority}\n";

        if ($task->due_date) {
            $daysUntil = now()->diffInDays($task->due_date, false);
            $context .= "Due Date: {$task->due_date->format('Y-m-d')} ({$daysUntil} days away)\n";
        } else {
            $context .= "Due Date: Not set\n";
        }

        if ($task->estimated_minutes) {
            $context .= "Estimated Time: {$task->estimated_time_human}\n";
        }

        if ($task->linkedGoal) {
            $context .= "Linked Goal: {$task->linkedGoal->title} (Progress: {$task->linkedGoal->progress_percentage}%)\n";
        }

        if ($task->tags->count() > 0) {
            $tags = $task->tags->pluck('name')->join(', ');
            $context .= "Tags: {$tags}\n";
        }

        $context .= "Source: {$task->source}\n";

        if ($task->notes) {
            $context .= "Notes: {$task->notes}\n";
        }

        return $context;
    }

    /**
     * Fallback rule-based priority scoring
     */
    protected function ruleBasedPriorityScore(Task $task): array
    {
        $score = 50; // Base score
        $reasons = [];

        // Factor 1: Due date urgency
        if ($task->due_date) {
            $daysUntil = now()->diffInDays($task->due_date, false);

            if ($daysUntil < 0) {
                $score += 30;
                $reasons[] = 'Task is overdue';
            } elseif ($daysUntil <= 1) {
                $score += 25;
                $reasons[] = 'Due within 24 hours';
            } elseif ($daysUntil <= 3) {
                $score += 15;
                $reasons[] = 'Due within 3 days';
            } elseif ($daysUntil <= 7) {
                $score += 10;
                $reasons[] = 'Due this week';
            }
        } else {
            $score -= 10;
        }

        // Factor 2: Current priority setting
        $score += match ($task->priority) {
            'urgent' => 20,
            'high' => 15,
            'medium' => 5,
            'low' => -5,
            default => 0,
        };

        // Factor 3: Linked to goal
        if ($task->linkedGoal) {
            $score += 10;
            $reasons[] = 'Connected to business goal';

            // Boost if goal is off-track
            if (in_array($task->linkedGoal->status, ['at_risk', 'off_track'])) {
                $score += 15;
                $reasons[] = 'Related goal needs attention';
            }
        }

        // Factor 4: Source importance
        if (in_array($task->source, ['document', 'ai_extracted'])) {
            $score += 5;
            $reasons[] = 'Extracted from important document';
        }

        // Factor 5: Has tags suggesting urgency
        if ($task->tags->contains('name', 'Urgent') || $task->tags->contains('name', 'Follow-up')) {
            $score += 10;
        }

        // Normalize score
        $score = min(100, max(1, $score));

        $reasoning = !empty($reasons)
            ? implode('. ', $reasons) . '.'
            : 'Priority calculated based on task attributes.';

        return [
            'score' => $score,
            'reasoning' => $reasoning,
            'suggested_priority' => $this->mapScoreToPriority($score),
        ];
    }

    /**
     * Map numerical score to priority level
     */
    protected function mapScoreToPriority(int $score): string
    {
        return match (true) {
            $score >= 80 => 'urgent',
            $score >= 60 => 'high',
            $score >= 40 => 'medium',
            default => 'low',
        };
    }

    /**
     * Batch calculate priorities for multiple tasks
     */
    public function batchCalculatePriorities(iterable $tasks): void
    {
        foreach ($tasks as $task) {
            $priority = $this->calculatePriorityScore($task);

            $task->update([
                'ai_priority_score' => $priority['score'],
                'ai_priority_reasoning' => $priority['reasoning'],
            ]);
        }
    }
}
