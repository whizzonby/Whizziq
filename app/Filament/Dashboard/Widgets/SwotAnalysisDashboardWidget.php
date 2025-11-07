<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\SwotAnalysis;
use App\Services\SwotGeneratorService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;

class SwotAnalysisDashboardWidget extends Widget
{
    protected static ?string $heading = '🎯 SWOT Analysis';

    protected static ?int $sort = 13;


    protected string $view = 'filament.dashboard.widgets.swot-analysis-dashboard-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $swotData = null;
    public ?array $strategicInsights = null;
    public bool $isGenerating = false;
    public bool $showAddForm = false;
    public string $addFormType = 'strength';
    public string $addFormDescription = '';
    public int $addFormPriority = 3;
    public ?int $editingId = null;

    public function mount()
    {
        $this->loadSwotData();
    }

    public function loadSwotData()
    {
        $user = auth()->user();

        $this->swotData = [
            'strengths' => SwotAnalysis::where('user_id', $user->id)
                ->strengths()
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'priority' => $item->priority,
                    'created_at' => $item->created_at->diffForHumans(),
                ])
                ->toArray(),

            'weaknesses' => SwotAnalysis::where('user_id', $user->id)
                ->weaknesses()
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'priority' => $item->priority,
                    'created_at' => $item->created_at->diffForHumans(),
                ])
                ->toArray(),

            'opportunities' => SwotAnalysis::where('user_id', $user->id)
                ->opportunities()
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'priority' => $item->priority,
                    'created_at' => $item->created_at->diffForHumans(),
                ])
                ->toArray(),

            'threats' => SwotAnalysis::where('user_id', $user->id)
                ->threats()
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'priority' => $item->priority,
                    'created_at' => $item->created_at->diffForHumans(),
                ])
                ->toArray(),
        ];

        // Generate strategic insights
        $this->strategicInsights = $this->generateStrategicInsights();
    }

    public function generateAISwot()
    {
        $this->isGenerating = true;

        try {
            $user = auth()->user();
            $generator = app(SwotGeneratorService::class);

            // Generate SWOT analysis using AI
            $result = $generator->generateSwotAnalysis($user->id);

            if ($result['success']) {
                $this->loadSwotData();

                Notification::make()
                    ->title('SWOT Analysis Generated')
                    ->body('AI has successfully generated your SWOT analysis based on your business metrics.')
                    ->success()
                    ->send();
            } else {
                throw new \Exception($result['message'] ?? 'Generation failed');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Generation Failed')
                ->body('Unable to generate SWOT analysis. ' . $e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }

    public function openAddForm(string $type)
    {
        $this->showAddForm = true;
        $this->addFormType = $type;
        $this->addFormDescription = '';
        $this->addFormPriority = 3;
        $this->editingId = null;
    }

    public function saveSwotItem()
    {
        $this->validate([
            'addFormDescription' => 'required|min:5|max:500',
            'addFormPriority' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();

        if ($this->editingId) {
            // Update existing
            $item = SwotAnalysis::where('user_id', $user->id)
                ->where('id', $this->editingId)
                ->first();

            if ($item) {
                $item->update([
                    'description' => $this->addFormDescription,
                    'priority' => $this->addFormPriority,
                ]);

                Notification::make()
                    ->title('Updated')
                    ->body('SWOT item updated successfully.')
                    ->success()
                    ->send();
            }
        } else {
            // Create new
            SwotAnalysis::create([
                'user_id' => $user->id,
                'type' => $this->addFormType,
                'description' => $this->addFormDescription,
                'priority' => $this->addFormPriority,
            ]);

            Notification::make()
                ->title('Added')
                ->body(ucfirst($this->addFormType) . ' added successfully.')
                ->success()
                ->send();
        }

        $this->showAddForm = false;
        $this->loadSwotData();
    }

    public function editSwotItem(int $id)
    {
        $user = auth()->user();
        $item = SwotAnalysis::where('user_id', $user->id)->where('id', $id)->first();

        if ($item) {
            $this->editingId = $item->id;
            $this->addFormType = $item->type;
            $this->addFormDescription = $item->description;
            $this->addFormPriority = $item->priority;
            $this->showAddForm = true;
        }
    }

    public function deleteSwotItem(int $id)
    {
        $user = auth()->user();

        SwotAnalysis::where('user_id', $user->id)
            ->where('id', $id)
            ->delete();

        Notification::make()
            ->title('Deleted')
            ->body('SWOT item deleted successfully.')
            ->success()
            ->send();

        $this->loadSwotData();
    }

    public function cancelForm()
    {
        $this->showAddForm = false;
        $this->editingId = null;
        $this->addFormDescription = '';
    }

    protected function generateStrategicInsights(): array
    {
        $insights = [];

        $strengthCount = count($this->swotData['strengths']);
        $weaknessCount = count($this->swotData['weaknesses']);
        $opportunityCount = count($this->swotData['opportunities']);
        $threatCount = count($this->swotData['threats']);

        // Strategic recommendations based on SWOT balance
        if ($strengthCount > $weaknessCount && $opportunityCount > $threatCount) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong Strategic Position',
                'description' => 'Your business shows more strengths than weaknesses and more opportunities than threats. Leverage your strengths to capitalize on opportunities.',
            ];
        }

        if ($weaknessCount > $strengthCount) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Focus on Improvement',
                'description' => 'You have more weaknesses than strengths. Prioritize addressing critical weaknesses to strengthen your competitive position.',
            ];
        }

        if ($threatCount > $opportunityCount) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Risk Mitigation Needed',
                'description' => 'External threats outweigh opportunities. Develop defensive strategies and explore new market opportunities.',
            ];
        }

        if ($opportunityCount > 3) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Growth Opportunities Available',
                'description' => "You've identified {$opportunityCount} opportunities. Prioritize the top 2-3 that align with your strengths.",
            ];
        }

        if ($strengthCount + $weaknessCount + $opportunityCount + $threatCount === 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Start Your SWOT Analysis',
                'description' => 'Use the AI-Generated SWOT button to automatically analyze your business, or manually add items to each quadrant.',
            ];
        }

        // Trend analysis
        $recentItems = SwotAnalysis::where('user_id', auth()->id())
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        if ($recentItems > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Recent Activity',
                'description' => "{$recentItems} SWOT items added in the last 30 days. Keep your analysis updated regularly.",
            ];
        }

        return $insights;
    }

    public function getPriorityColor(int $priority): string
    {
        return match($priority) {
            5 => 'danger',
            4 => 'warning',
            3 => 'primary',
            2 => 'info',
            1 => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityLabel(int $priority): string
    {
        return match($priority) {
            5 => 'Critical',
            4 => 'High',
            3 => 'Medium',
            2 => 'Low',
            1 => 'Very Low',
            default => 'Medium',
        };
    }

    public function getQuadrantStats(): array
    {
        return [
            'total_items' => array_sum([
                count($this->swotData['strengths']),
                count($this->swotData['weaknesses']),
                count($this->swotData['opportunities']),
                count($this->swotData['threats']),
            ]),
            'high_priority_count' => collect($this->swotData)
                ->flatten(1)
                ->where('priority', '>=', 4)
                ->count(),
        ];
    }
}
