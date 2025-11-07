<?php

namespace App\Filament\Dashboard\Resources\GoalResource\Pages;

use App\Filament\Dashboard\Resources\GoalResource;
use App\Models\GoalCheckIn;
use App\Services\OpenAIService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class CheckInGoal extends Page
{
    protected static string $resource = GoalResource::class;

    protected string $view = 'filament.dashboard.resources.goal-resource.check-in-goal';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Weekly Check-in')
                    ->description('Update your progress and reflect on this week')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Progress Notes')
                            ->placeholder('What progress did you make this week?')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('sentiment')
                            ->label('How do you feel about progress?')
                            ->options([
                                'positive' => '😊 On track and feeling good',
                                'neutral' => '😐 Making progress, some challenges',
                                'negative' => '😞 Behind schedule, need help',
                            ])
                            ->native(false)
                            ->required(),

                        Forms\Components\Textarea::make('blockers')
                            ->label('What\'s blocking you?')
                            ->placeholder('Any obstacles or challenges?')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('next_steps')
                            ->label('Next Steps')
                            ->placeholder('What will you focus on next week?')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Create check-in
        GoalCheckIn::create([
            'goal_id' => $this->record->id,
            'user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
            'sentiment' => $data['sentiment'] ?? null,
            'blockers' => $data['blockers'] ?? null,
            'next_steps' => $data['next_steps'] ?? null,
            'progress_update' => $this->record->progress_percentage,
        ]);

        // Update goal
        $this->record->update([
            'last_check_in_at' => now(),
            'check_in_count' => $this->record->check_in_count + 1,
        ]);

        // Get AI suggestions if off-track
        if (in_array($this->record->status, ['at_risk', 'off_track']) && config('services.openai.key')) {
            try {
                $this->generateAISuggestions();
            } catch (\Exception $e) {
                // Silently fail AI suggestions
            }
        }

        Notification::make()
            ->title('Check-in recorded!')
            ->body('Your progress has been saved.')
            ->success()
            ->send();

        $this->redirect(GoalResource::getUrl('view', ['record' => $this->record]));
    }

    protected function generateAISuggestions(): void
    {
        $openAI = app(OpenAIService::class);

        $context = "Goal: {$this->record->title}\n";
        $context .= "Progress: {$this->record->progress_percentage}%\n";
        $context .= "Status: {$this->record->status}\n";
        $context .= "Days Remaining: {$this->record->days_remaining}\n\n";

        $context .= "Key Results:\n";
        foreach ($this->record->keyResults as $kr) {
            $context .= "- {$kr->title}: {$kr->current_value}/{$kr->target_value} ({$kr->progress_percentage}%)\n";
        }

        $suggestions = $openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a business coach helping entrepreneurs achieve their goals. Provide specific, actionable advice.',
            ],
            [
                'role' => 'user',
                'content' => "This goal is off-track. Provide 3-5 specific, actionable recommendations to get back on track:\n\n{$context}",
            ],
        ], [
            'max_tokens' => 500,
        ]);

        if ($suggestions) {
            $this->record->update(['ai_suggestions' => $suggestions]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Goal')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => GoalResource::getUrl('view', ['record' => $this->record])),
        ];
    }
}
