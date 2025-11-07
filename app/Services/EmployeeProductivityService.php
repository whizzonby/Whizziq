<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeTask;
use App\Models\AttendanceRecord;
use App\Models\SentimentSurvey;
use App\Models\EmployeeProductivityMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeProductivityService
{
    /**
     * Calculate and store productivity metrics for a specific employee and date
     */
    public function calculateProductivityForEmployee(Employee $employee, Carbon $date): EmployeeProductivityMetric
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get task metrics
        $tasksCompleted = EmployeeTask::where('employee_id', $employee->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startOfDay, $endOfDay])
            ->count();

        $tasksPending = EmployeeTask::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->where('created_at', '<=', $endOfDay)
            ->count();

        // Calculate on-time completion rate
        $completedTasks = EmployeeTask::where('employee_id', $employee->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startOfDay, $endOfDay])
            ->get();

        $onTimeCount = $completedTasks->filter(function ($task) {
            return $task->completed_at && $task->due_date &&
                   $task->completed_at->lte($task->due_date);
        })->count();

        $onTimeRate = $completedTasks->count() > 0
            ? ($onTimeCount / $completedTasks->count()) * 100
            : 0;

        // Get attendance for the day
        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $date->toDateString())
            ->first();

        $attendancePercentage = $attendance && in_array($attendance->status, ['present', 'late'])
            ? 100
            : 0;

        // Get sentiment score for the day (or recent average)
        $sentimentScore = SentimentSurvey::where('employee_id', $employee->id)
            ->where('survey_date', '>=', $date->copy()->subDays(7))
            ->where('survey_date', '<=', $date)
            ->avg('score') ?? 3.0; // Default to neutral

        // Calculate output value (weighted by task priority)
        $outputValue = $completedTasks->sum(function ($task) {
            return match ($task->priority) {
                'high' => 150,
                'medium' => 100,
                'low' => 50,
                default => 75,
            };
        });

        // Calculate overall productivity score
        $productivityScore = $this->calculateProductivityScore(
            $tasksCompleted,
            $tasksPending,
            $onTimeRate,
            $attendancePercentage,
            $sentimentScore
        );

        // Create or update metric
        return EmployeeProductivityMetric::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
            ],
            [
                'user_id' => $employee->user_id,
                'tasks_completed' => $tasksCompleted,
                'tasks_pending' => $tasksPending,
                'on_time_completion_rate' => round($onTimeRate, 2),
                'output_value' => $outputValue,
                'attendance_percentage' => $attendancePercentage,
                'sentiment_score' => round($sentimentScore, 2),
                'productivity_score' => round($productivityScore, 2),
            ]
        );
    }

    /**
     * Calculate productivity score based on weighted factors
     */
    protected function calculateProductivityScore(
        int $tasksCompleted,
        int $tasksPending,
        float $onTimeRate,
        float $attendancePercentage,
        float $sentimentScore
    ): float {
        $weights = [
            'task_completion' => 0.30,
            'on_time_rate' => 0.25,
            'attendance' => 0.20,
            'sentiment' => 0.15,
            'task_efficiency' => 0.10,
        ];

        // Task completion score (0-100)
        $totalTasks = $tasksCompleted + $tasksPending;
        $taskCompletionScore = $totalTasks > 0
            ? ($tasksCompleted / $totalTasks) * 100
            : 0;

        // Sentiment score (convert 1-5 scale to 0-100)
        $sentimentPercentage = ($sentimentScore / 5) * 100;

        // Task efficiency (reward high completion with low pending)
        $taskEfficiencyScore = $tasksCompleted > 0
            ? min(100, ($tasksCompleted / max(1, $tasksPending)) * 50)
            : 0;

        // Calculate weighted score
        $productivityScore =
            ($taskCompletionScore * $weights['task_completion']) +
            ($onTimeRate * $weights['on_time_rate']) +
            ($attendancePercentage * $weights['attendance']) +
            ($sentimentPercentage * $weights['sentiment']) +
            ($taskEfficiencyScore * $weights['task_efficiency']);

        return min(100, max(0, $productivityScore));
    }

    /**
     * Calculate productivity for all active employees for a given date
     */
    public function calculateProductivityForAllEmployees(Carbon $date, ?int $userId = null): int
    {
        $query = Employee::where('employment_status', 'active');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $employees = $query->get();
        $count = 0;

        foreach ($employees as $employee) {
            try {
                $this->calculateProductivityForEmployee($employee, $date);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to calculate productivity for employee ' . $employee->id, [
                    'error' => $e->getMessage(),
                    'date' => $date->toDateString(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Calculate productivity for a date range
     */
    public function calculateProductivityForDateRange(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $metrics = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $metrics[] = $this->calculateProductivityForEmployee($employee, $currentDate);
            $currentDate->addDay();
        }

        return $metrics;
    }

    /**
     * Get productivity trends for an employee
     */
    public function getProductivityTrends(Employee $employee, int $days = 30): array
    {
        $metrics = EmployeeProductivityMetric::where('employee_id', $employee->id)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        return [
            'dates' => $metrics->pluck('date')->map(fn($d) => $d->format('M d'))->toArray(),
            'productivity_scores' => $metrics->pluck('productivity_score')->toArray(),
            'tasks_completed' => $metrics->pluck('tasks_completed')->toArray(),
            'attendance' => $metrics->pluck('attendance_percentage')->toArray(),
            'sentiment' => $metrics->pluck('sentiment_score')->toArray(),
        ];
    }

    /**
     * Detect productivity anomalies
     */
    public function detectProductivityAnomalies(Employee $employee, int $days = 30): array
    {
        $metrics = EmployeeProductivityMetric::where('employee_id', $employee->id)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        if ($metrics->count() < 7) {
            return [];
        }

        $scores = $metrics->pluck('productivity_score')->toArray();
        $mean = array_sum($scores) / count($scores);

        $variance = array_sum(array_map(function ($score) use ($mean) {
            return pow($score - $mean, 2);
        }, $scores)) / count($scores);

        $stdDev = sqrt($variance);

        $anomalies = [];
        foreach ($metrics as $metric) {
            $zScore = $stdDev > 0 ? ($metric->productivity_score - $mean) / $stdDev : 0;

            if (abs($zScore) > 2) {
                $anomalies[] = [
                    'date' => $metric->date->format('Y-m-d'),
                    'score' => $metric->productivity_score,
                    'z_score' => round($zScore, 2),
                    'type' => $zScore > 0 ? 'high' : 'low',
                    'severity' => abs($zScore) > 3 ? 'critical' : 'warning',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Get department productivity summary
     */
    public function getDepartmentProductivitySummary(int $departmentId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $metrics = EmployeeProductivityMetric::whereHas('employee', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })
        ->where('date', '>=', $startDate)
        ->get();

        return [
            'avg_productivity' => round($metrics->avg('productivity_score'), 2),
            'avg_tasks_completed' => round($metrics->avg('tasks_completed'), 2),
            'avg_on_time_rate' => round($metrics->avg('on_time_completion_rate'), 2),
            'avg_attendance' => round($metrics->avg('attendance_percentage'), 2),
            'avg_sentiment' => round($metrics->avg('sentiment_score'), 2),
            'total_output_value' => round($metrics->sum('output_value'), 2),
        ];
    }
}
