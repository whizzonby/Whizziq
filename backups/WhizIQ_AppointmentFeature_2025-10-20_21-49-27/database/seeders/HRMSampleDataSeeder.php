<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\Team;
use App\Models\Employee;
use App\Models\EmployeeTask;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\PerformanceReview;
use App\Models\PerformanceTarget;
use App\Models\SentimentSurvey;
use App\Models\EmployeeProductivityMetric;
use Carbon\Carbon;

class HRMSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first user (business owner)
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        $this->command->info('Creating HRM sample data for user: ' . $user->name);

        // Create Departments
        $departments = [
            [
                'name' => 'Engineering',
                'description' => 'Software development and technical operations',
                'budget' => 50000.00,
                'location' => 'Main Office',
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing and customer acquisition',
                'budget' => 25000.00,
                'location' => 'Main Office',
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales and customer relations',
                'budget' => 30000.00,
                'location' => 'Main Office',
            ],
            [
                'name' => 'HR',
                'description' => 'Human resources and administration',
                'budget' => 15000.00,
                'location' => 'Main Office',
            ],
        ];

        $createdDepartments = [];
        foreach ($departments as $deptData) {
            $deptData['user_id'] = $user->id;
            $createdDepartments[] = Department::create($deptData);
        }

        // Create Teams
        $teams = [
            ['department_id' => $createdDepartments[0]->id, 'name' => 'Frontend Team', 'description' => 'React and Vue.js development'],
            ['department_id' => $createdDepartments[0]->id, 'name' => 'Backend Team', 'description' => 'API and database development'],
            ['department_id' => $createdDepartments[1]->id, 'name' => 'Digital Marketing', 'description' => 'Online marketing campaigns'],
            ['department_id' => $createdDepartments[2]->id, 'name' => 'Enterprise Sales', 'description' => 'B2B sales team'],
            ['department_id' => $createdDepartments[3]->id, 'name' => 'Recruitment', 'description' => 'Talent acquisition'],
        ];

        $createdTeams = [];
        foreach ($teams as $teamData) {
            $teamData['user_id'] = $user->id;
            $createdTeams[] = Team::create($teamData);
        }

        // Create Employees
        $employees = [
            [
                'employee_id' => 'EMP001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@company.com',
                'phone' => '+1-555-0101',
                'position' => 'Senior Software Engineer',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'hire_date' => Carbon::now()->subMonths(24),
                'salary' => 85000.00,
                'pay_frequency' => 'monthly',
                'department_id' => $createdDepartments[0]->id,
                'team_id' => $createdTeams[0]->id,
            ],
            [
                'employee_id' => 'EMP002',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@company.com',
                'phone' => '+1-555-0102',
                'position' => 'Marketing Manager',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'hire_date' => Carbon::now()->subMonths(18),
                'salary' => 70000.00,
                'pay_frequency' => 'monthly',
                'department_id' => $createdDepartments[1]->id,
                'team_id' => $createdTeams[2]->id,
            ],
            [
                'employee_id' => 'EMP003',
                'first_name' => 'Mike',
                'last_name' => 'Davis',
                'email' => 'mike.davis@company.com',
                'phone' => '+1-555-0103',
                'position' => 'Sales Representative',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'hire_date' => Carbon::now()->subMonths(12),
                'salary' => 60000.00,
                'pay_frequency' => 'monthly',
                'department_id' => $createdDepartments[2]->id,
                'team_id' => $createdTeams[3]->id,
            ],
            [
                'employee_id' => 'EMP004',
                'first_name' => 'Emily',
                'last_name' => 'Wilson',
                'email' => 'emily.wilson@company.com',
                'phone' => '+1-555-0104',
                'position' => 'HR Specialist',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'hire_date' => Carbon::now()->subMonths(6),
                'salary' => 55000.00,
                'pay_frequency' => 'monthly',
                'department_id' => $createdDepartments[3]->id,
                'team_id' => $createdTeams[4]->id,
            ],
            [
                'employee_id' => 'EMP005',
                'first_name' => 'David',
                'last_name' => 'Brown',
                'email' => 'david.brown@company.com',
                'phone' => '+1-555-0105',
                'position' => 'Backend Developer',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'hire_date' => Carbon::now()->subMonths(3),
                'salary' => 75000.00,
                'pay_frequency' => 'monthly',
                'department_id' => $createdDepartments[0]->id,
                'team_id' => $createdTeams[1]->id,
            ],
        ];

        $createdEmployees = [];
        foreach ($employees as $empData) {
            $empData['user_id'] = $user->id;
            $createdEmployees[] = Employee::create($empData);
        }

        // Create Employee Tasks
        $tasks = [
            [
                'employee_id' => $createdEmployees[0]->id,
                'assigned_by' => $user->id,
                'title' => 'Implement user authentication',
                'description' => 'Create login and registration system',
                'priority' => 'high',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(5),
                'completed_at' => Carbon::now()->subDays(3),
            ],
            [
                'employee_id' => $createdEmployees[0]->id,
                'assigned_by' => $user->id,
                'title' => 'Optimize database queries',
                'description' => 'Improve performance of slow queries',
                'priority' => 'medium',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(3),
            ],
            [
                'employee_id' => $createdEmployees[1]->id,
                'assigned_by' => $user->id,
                'title' => 'Launch social media campaign',
                'description' => 'Create and launch Facebook/Instagram ads',
                'priority' => 'high',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(2),
                'completed_at' => Carbon::now()->subDays(1),
            ],
            [
                'employee_id' => $createdEmployees[2]->id,
                'assigned_by' => $user->id,
                'title' => 'Follow up with leads',
                'description' => 'Contact 20 potential customers',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(2),
            ],
            [
                'employee_id' => $createdEmployees[4]->id,
                'assigned_by' => $user->id,
                'title' => 'API documentation',
                'description' => 'Write comprehensive API docs',
                'priority' => 'low',
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(7),
            ],
        ];

        foreach ($tasks as $taskData) {
            $taskData['user_id'] = $user->id;
            EmployeeTask::create($taskData);
        }

        // Create Attendance Records (last 30 days)
        foreach ($createdEmployees as $employee) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);
                
                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                $status = match (rand(1, 100)) {
                    1, 2, 3 => 'absent', // 3% absent
                    4, 5 => 'late', // 2% late
                    default => 'present', // 95% present
                };

                AttendanceRecord::create([
                    'user_id' => $user->id,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => $status,
                    'check_in_time' => $status === 'present' ? $date->setTime(9, rand(0, 30)) : null,
                    'check_out_time' => $status === 'present' ? $date->setTime(17, rand(0, 30)) : null,
                    'hours_worked' => $status === 'present' ? rand(7, 9) : 0,
                    'marked_by' => $user->id,
                ]);
            }
        }

        // Create Leave Balances
        foreach ($createdEmployees as $employee) {
            LeaveBalance::create([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'year' => now()->year,
                'sick_days' => 10,
                'vacation_days' => 20,
                'personal_days' => 5,
                'sick_used' => rand(0, 3),
                'vacation_used' => rand(0, 8),
                'personal_used' => rand(0, 2),
            ]);
        }

        // Create Performance Reviews
        foreach ($createdEmployees as $employee) {
            PerformanceReview::create([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'reviewer_id' => $user->id,
                'review_period' => 'Q4 2024',
                'review_date' => Carbon::now()->subDays(rand(1, 30)),
                'overall_rating' => rand(70, 95),
                'strengths' => 'Strong technical skills and good team collaboration.',
                'areas_for_improvement' => 'Could improve time management and communication.',
                'goals_for_next_period' => ['Complete advanced training', 'Lead a project'],
                'status' => 'acknowledged',
            ]);
        }

        // Create Sentiment Surveys
        foreach ($createdEmployees as $employee) {
            for ($i = 0; $i < rand(3, 8); $i++) {
                SentimentSurvey::create([
                    'user_id' => $user->id,
                    'employee_id' => $employee->id,
                    'survey_date' => Carbon::now()->subDays(rand(1, 30)),
                    'score' => rand(3, 5), // Mostly positive
                    'comments' => 'Great team environment and interesting projects.',
                    'is_anonymous' => true,
                    'survey_type' => 'pulse',
                ]);
            }
        }

        // Create Productivity Metrics
        foreach ($createdEmployees as $employee) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);
                
                EmployeeProductivityMetric::create([
                    'user_id' => $user->id,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'tasks_completed' => rand(2, 8),
                    'tasks_pending' => rand(1, 5),
                    'on_time_completion_rate' => rand(75, 95),
                    'output_value' => rand(500, 1500),
                    'attendance_percentage' => rand(85, 100),
                    'sentiment_score' => rand(3.5, 5.0),
                    'productivity_score' => rand(70, 95),
                ]);
            }
        }

        $this->command->info('✅ HRM sample data created successfully!');
        $this->command->info('Created:');
        $this->command->info('- ' . count($createdDepartments) . ' departments');
        $this->command->info('- ' . count($createdTeams) . ' teams');
        $this->command->info('- ' . count($createdEmployees) . ' employees');
        $this->command->info('- ' . count($tasks) . ' tasks');
        $this->command->info('- 30 days of attendance records');
        $this->command->info('- Leave balances for all employees');
        $this->command->info('- Performance reviews');
        $this->command->info('- Sentiment surveys');
        $this->command->info('- Productivity metrics');
    }
}