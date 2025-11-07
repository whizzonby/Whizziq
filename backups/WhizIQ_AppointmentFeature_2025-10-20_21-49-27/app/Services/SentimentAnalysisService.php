<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SentimentSurvey;
use App\Models\EmployeeProductivityMetric;
use App\Models\AttendanceRecord;
use App\Models\EmployeeTask;

class SentimentAnalysisService
{
    /**
     * Get team morale summary
     */
    public function getTeamMoraleSummary(int $userId): array
    {
        $last30Days = now()->subDays(30);
        
        // Get average sentiment score
        $avgScore = SentimentSurvey::whereHas('employee', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('survey_date', '>=', $last30Days)
        ->avg('score') ?? 0;

        // Get sentiment trend
        $recentScore = SentimentSurvey::whereHas('employee', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereBetween('survey_date', [now()->subDays(15), now()])
        ->avg('score') ?? 0;

        $previousScore = SentimentSurvey::whereHas('employee', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereBetween('survey_date', [now()->subDays(30), now()->subDays(15)])
        ->avg('score') ?? 0;

        $trend = 'stable';
        if ($recentScore > $previousScore + 0.2) {
            $trend = 'improving';
        } elseif ($recentScore < $previousScore - 0.2) {
            $trend = 'declining';
        }

        // Determine status
        $status = 'good';
        if ($avgScore < 3) {
            $status = 'poor';
        } elseif ($avgScore < 4) {
            $status = 'fair';
        }

        return [
            'avg_score' => round($avgScore, 1),
            'trend' => $trend,
            'status' => $status,
            'total_surveys' => SentimentSurvey::whereHas('employee', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->where('survey_date', '>=', $last30Days)->count(),
        ];
    }

    /**
     * Detect burnout indicators for an employee
     */
    public function detectBurnoutIndicators(Employee $employee): array
    {
        $last30Days = now()->subDays(30);
        
        // Check sentiment trends
        $recentSentiment = SentimentSurvey::where('employee_id', $employee->id)
            ->whereBetween('survey_date', [now()->subDays(15), now()])
            ->avg('score') ?? 0;

        $previousSentiment = SentimentSurvey::where('employee_id', $employee->id)
            ->whereBetween('survey_date', [now()->subDays(30), now()->subDays(15)])
            ->avg('score') ?? 0;

        // Check productivity trends
        $recentProductivity = EmployeeProductivityMetric::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->subDays(15), now()])
            ->avg('productivity_score') ?? 0;

        $previousProductivity = EmployeeProductivityMetric::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->subDays(30), now()->subDays(15)])
            ->avg('productivity_score') ?? 0;

        // Check attendance
        $absenteeismRate = $this->getEmployeeAbsenteeismRate($employee->id);

        // Check task completion
        $overdueTasks = EmployeeTask::where('employee_id', $employee->id)
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        // Calculate burnout risk
        $riskFactors = 0;
        $severity = 'low';

        if ($recentSentiment < 3 && $recentSentiment < $previousSentiment - 0.5) {
            $riskFactors++;
        }

        if ($recentProductivity < 60 && $recentProductivity < $previousProductivity - 10) {
            $riskFactors++;
        }

        if ($absenteeismRate > 15) {
            $riskFactors++;
        }

        if ($overdueTasks > 3) {
            $riskFactors++;
        }

        $hasBurnoutRisk = $riskFactors >= 2;

        if ($riskFactors >= 3) {
            $severity = 'high';
        } elseif ($riskFactors >= 2) {
            $severity = 'medium';
        }

        return [
            'has_burnout_risk' => $hasBurnoutRisk,
            'severity' => $severity,
            'risk_factors' => $riskFactors,
            'indicators' => [
                'sentiment_decline' => $recentSentiment < $previousSentiment - 0.5,
                'productivity_decline' => $recentProductivity < $previousProductivity - 10,
                'high_absenteeism' => $absenteeismRate > 15,
                'task_overload' => $overdueTasks > 3,
            ],
        ];
    }

    /**
     * Get employee absenteeism rate
     */
    private function getEmployeeAbsenteeismRate(int $employeeId): float
    {
        $last30Days = now()->subDays(30);
        
        $totalRecords = AttendanceRecord::where('employee_id', $employeeId)
            ->where('date', '>=', $last30Days)
            ->count();

        $absentRecords = AttendanceRecord::where('employee_id', $employeeId)
            ->where('date', '>=', $last30Days)
            ->where('status', 'absent')
            ->count();

        return $totalRecords > 0 ? ($absentRecords / $totalRecords) * 100 : 0;
    }

    /**
     * Analyze sentiment patterns
     */
    public function analyzeSentimentPatterns(int $userId): array
    {
        $last90Days = now()->subDays(90);
        
        $surveys = SentimentSurvey::whereHas('employee', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('survey_date', '>=', $last90Days)
        ->orderBy('survey_date')
        ->get();

        $patterns = [
            'weekly_trends' => [],
            'monthly_trends' => [],
            'common_issues' => [],
            'positive_feedback' => [],
        ];

        // Analyze weekly trends
        $weeklyData = $surveys->groupBy(function ($survey) {
            return $survey->survey_date->format('Y-W');
        })->map(function ($weekSurveys) {
            return $weekSurveys->avg('score');
        });

        $patterns['weekly_trends'] = $weeklyData->toArray();

        // Analyze monthly trends
        $monthlyData = $surveys->groupBy(function ($survey) {
            return $survey->survey_date->format('Y-m');
        })->map(function ($monthSurveys) {
            return $monthSurveys->avg('score');
        });

        $patterns['monthly_trends'] = $monthlyData->toArray();

        // Analyze comments for common issues
        $negativeComments = $surveys->where('score', '<=', 2)->pluck('comments')->filter();
        $positiveComments = $surveys->where('score', '>=', 4)->pluck('comments')->filter();

        $patterns['common_issues'] = $this->extractCommonIssues($negativeComments);
        $patterns['positive_feedback'] = $this->extractPositiveFeedback($positiveComments);

        return $patterns;
    }

    /**
     * Extract common issues from negative comments
     */
    private function extractCommonIssues($comments): array
    {
        $issues = [];
        $keywords = [
            'stress' => 'Workload stress',
            'overwhelmed' => 'Feeling overwhelmed',
            'burnout' => 'Burnout symptoms',
            'frustrated' => 'Frustration',
            'difficult' => 'Difficult situations',
            'challenging' => 'Challenging circumstances',
        ];

        foreach ($comments as $comment) {
            $comment = strtolower($comment);
            foreach ($keywords as $keyword => $issue) {
                if (str_contains($comment, $keyword)) {
                    $issues[$issue] = ($issues[$issue] ?? 0) + 1;
                }
            }
        }

        return $issues;
    }

    /**
     * Extract positive feedback from comments
     */
    private function extractPositiveFeedback($comments): array
    {
        $feedback = [];
        $keywords = [
            'happy' => 'Happiness',
            'satisfied' => 'Satisfaction',
            'motivated' => 'Motivation',
            'excited' => 'Excitement',
            'great' => 'Positive experience',
            'excellent' => 'Excellent work',
        ];

        foreach ($comments as $comment) {
            $comment = strtolower($comment);
            foreach ($keywords as $keyword => $type) {
                if (str_contains($comment, $keyword)) {
                    $feedback[$type] = ($feedback[$type] ?? 0) + 1;
                }
            }
        }

        return $feedback;
    }
}
