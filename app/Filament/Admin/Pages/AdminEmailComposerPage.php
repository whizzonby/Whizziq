<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Services\EmailCampaignService;
use App\Services\OpenAIService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use UnitEnum;

class AdminEmailComposerPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.admin-email-composer-page';

    protected static UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Send Email to Users';

    protected static ?string $navigationLabel = 'Email Users';

    public ?array $data = [];
    public ?string $previewSubject = null;
    public ?string $previewBody = null;
    public bool $showPreviewModal = false;

    public function mount(): void
    {
        $this->form->fill([
            'template_id' => null,
            'from_name' => config('mail.from.name'),
            'from_email' => config('mail.from.address'),
            'send_now' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('AI Email Generation')
                    ->schema([
                        Textarea::make('ai_purpose')
                            ->label('Describe Email Purpose')
                            ->placeholder('e.g., Announce new feature, System maintenance, Special offer, etc.')
                            ->rows(2)
                            ->helperText('Tell AI what you want to write about')
                            ->columnSpanFull(),

                        Select::make('ai_tone')
                            ->label('Tone')
                            ->options([
                                'professional' => 'Professional',
                                'friendly' => 'Friendly',
                                'casual' => 'Casual',
                                'formal' => 'Formal',
                            ])
                            ->default('professional'),

                        Actions::make([
                            Action::make('generate_email')
                                ->label('Generate Email with AI')
                                ->icon('heroicon-o-sparkles')
                                ->color('primary')
                                ->action(function (Set $set, Get $get) {
                                    $this->generateEmail($set, $get);
                                }),
                        ]),
                    ])
                    ->collapsed()
                    ->description('Use AI to generate email content automatically'),

                Section::make('Recipients')
                    ->schema([
                        Select::make('recipient_type')
                            ->label('Send To')
                            ->options([
                                'all_users' => 'All Users',
                                'verified_users' => 'Verified Users Only',
                                'subscribed_users' => 'Subscribed Users',
                                'trial_users' => 'Trial Users',
                                'specific_users' => 'Specific Users',
                            ])
                            ->default('verified_users')
                            ->required()
                            ->live()
                            ->helperText('Select which users should receive this email'),

                        Select::make('user_ids')
                            ->label('Select Specific Users')
                            ->multiple()
                            ->searchable()
                            ->options(function () {
                                return User::orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => $user->name . ' (' . $user->email . ')'
                                    ]);
                            })
                            ->required(fn (Get $get) => $get('recipient_type') === 'specific_users')
                            ->hidden(fn (Get $get) => $get('recipient_type') !== 'specific_users')
                            ->helperText('Select specific users to send this email to'),

                        Placeholder::make('recipient_count')
                            ->label('Estimated Recipients')
                            ->content(function (Get $get) {
                                $type = $get('recipient_type');

                                $count = match($type) {
                                    'all_users' => User::count(),
                                    'verified_users' => User::whereNotNull('email_verified_at')->count(),
                                    'subscribed_users' => User::whereHas('subscriptions', function ($q) {
                                        $q->where('status', 'active');
                                    })->count(),
                                    'trial_users' => User::whereHas('subscriptions', function ($q) {
                                        $q->where('status', 'trialing');
                                    })->count(),
                                    'specific_users' => count($get('user_ids') ?? []),
                                    default => 0,
                                };

                                return "{$count} users will receive this email";
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Email Template (Optional)')
                    ->schema([
                        Select::make('template_id')
                            ->label('Use Template')
                            ->options(function () {
                                return \App\Models\EmailTemplate::where('user_id', auth()->id())
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $template = \App\Models\EmailTemplate::find($state);
                                    if ($template) {
                                        $set('subject', $template->subject);
                                        $set('body', $template->body);
                                    }
                                }
                            })
                            ->helperText('Load a template or compose from scratch'),
                    ]),

                Section::make('Email Content')
                    ->schema([
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Use {{variable_name}} for dynamic content'),

                        Actions::make([
                            Action::make('preview_email')
                                ->label('Preview Email')
                                ->icon('heroicon-o-eye')
                                ->color('gray')
                                ->action(function (Get $get) {
                                    $this->showPreview($get);
                                })
                                ->visible(fn (Get $get) => !empty($get('subject')) && !empty($get('body'))),
                        ]),

                        RichEditor::make('body')
                            ->required()
                            ->label('Email Body')
                            ->helperText('Use {{variable_name}} for dynamic content - Variables will be replaced for each recipient')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),

                        Placeholder::make('available_variables')
                            ->label('Available Variables')
                            ->content(function () {
                                $variables = self::getAvailableUserVariables();
                                $list = collect($variables)
                                    ->map(fn($label, $key) => "{{" . $key . "}} - " . $label)
                                    ->join(", ");
                                return $list;
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Scheduling')
                    ->schema([
                        Toggle::make('send_now')
                            ->label('Send Immediately')
                            ->default(true)
                            ->live()
                            ->helperText('Turn off to schedule for later'),

                        DateTimePicker::make('scheduled_at')
                            ->label('Schedule For')
                            ->native(false)
                            ->seconds(false)
                            ->minDate(now())
                            ->helperText('Select when to send this email')
                            ->hidden(fn (Get $get) => $get('send_now') === true)
                            ->required(fn (Get $get) => $get('send_now') === false),
                    ])
                    ->columns(2),

                Section::make('Attachments (Optional)')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Upload Files')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(10240) // 10MB per file
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'text/plain',
                                'application/zip',
                            ])
                            ->directory('admin-email-attachments')
                            ->visibility('private')
                            ->helperText('Max 5 files, 10MB each. Supported: PDF, Word, Excel, Images, Text, ZIP'),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Section::make('Sender Information')
                    ->schema([
                        TextInput::make('from_name')
                            ->label('From Name')
                            ->maxLength(255),

                        TextInput::make('from_email')
                            ->label('From Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('reply_to')
                            ->label('Reply To Email')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Leave blank to use From Email'),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function generateEmail(Set $set, Get $get)
    {
        $purpose = $get('ai_purpose');
        $tone = $get('ai_tone') ?? 'professional';

        if (empty($purpose)) {
            Notification::make()
                ->title('Purpose Required')
                ->body('Please describe what you want the email to be about.')
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Generating Email...')
            ->body('AI is writing your email. This may take a few seconds.')
            ->info()
            ->send();

        try {
            $openAI = app(OpenAIService::class);
            $result = $openAI->generateEmail($purpose, [], $tone);

            if ($result && isset($result['subject']) && isset($result['body'])) {
                $set('subject', $result['subject']);
                $set('body', $result['body']);

                Notification::make()
                    ->title('Email Generated!')
                    ->body('AI has generated your email. Review and customize as needed.')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('Failed to generate email content');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Generation Failed')
                ->body('Could not generate email. Please check your OpenAI configuration.')
                ->danger()
                ->send();
        }
    }

    /**
     * Get available user variables for email templates
     */
    public static function getAvailableUserVariables(): array
    {
        return [
            // User Info
            'name' => 'User Full Name',
            'first_name' => 'First Name',
            'email' => 'Email Address',

            // System Info
            'app_name' => 'Application Name',
            'current_date' => 'Current Date',
            'current_year' => 'Current Year',
        ];
    }

    /**
     * Replace variables in email content with actual user data
     */
    protected function replaceVariables(string $content, User $user): string
    {
        // Split name into first and last (simple approach)
        $nameParts = explode(' ', $user->name, 2);
        $firstName = $nameParts[0] ?? $user->name;

        $variables = [
            'name' => $user->name,
            'first_name' => $firstName,
            'email' => $user->email,
            'app_name' => config('app.name'),
            'current_date' => now()->format('F j, Y'),
            'current_year' => now()->format('Y'),
        ];

        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value ?? '', $content);
        }

        return $content;
    }

    public function sendEmail()
    {
        $data = $this->form->getState();

        // Get users based on recipient type
        $users = $this->getTargetedUsers($data);

        if ($users->isEmpty()) {
            Notification::make()
                ->title('No Recipients')
                ->body('No users match the selected criteria.')
                ->warning()
                ->send();
            return;
        }

        // Check if scheduling
        if (!$data['send_now'] && !empty($data['scheduled_at'])) {
            $this->scheduleEmail($data, $users);
            return;
        }

        // Send immediately
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                // Replace variables with user-specific data
                $personalizedSubject = $this->replaceVariables($data['subject'], $user);
                $personalizedBody = $this->replaceVariables($data['body'], $user);

                \Mail::send('emails.admin-campaign', [
                    'user' => $user,
                    'subject' => $personalizedSubject,
                    'body' => $personalizedBody,
                ], function ($message) use ($user, $data, $personalizedSubject) {
                    $message->to($user->email, $user->name)
                        ->subject($personalizedSubject);

                    if (!empty($data['from_email']) && !empty($data['from_name'])) {
                        $message->from($data['from_email'], $data['from_name']);
                    } elseif (!empty($data['from_email'])) {
                        $message->from($data['from_email']);
                    }

                    if (!empty($data['reply_to'])) {
                        $message->replyTo($data['reply_to']);
                    }

                    if (!empty($data['attachments'])) {
                        foreach ($data['attachments'] as $attachment) {
                            if (file_exists(storage_path('app/' . $attachment))) {
                                $message->attach(storage_path('app/' . $attachment));
                            }
                        }
                    }
                });

                $sent++;
            } catch (\Exception $e) {
                $failed++;
                \Log::error('Failed to send admin email to user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            Notification::make()
                ->title('Emails Sent Successfully')
                ->body("Sent {$sent} email(s). " . ($failed > 0 ? "{$failed} failed." : ''))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to Send Emails')
                ->body("All {$failed} email(s) failed to send. Please check your email configuration.")
                ->danger()
                ->send();
        }

        // Reset form
        $this->form->fill([
            'recipient_type' => 'verified_users',
            'user_ids' => [],
            'template_id' => null,
            'subject' => '',
            'body' => '',
            'from_name' => config('mail.from.name'),
            'from_email' => config('mail.from.address'),
            'reply_to' => '',
            'send_now' => true,
            'scheduled_at' => null,
            'attachments' => [],
        ]);
    }

    protected function getTargetedUsers(array $data)
    {
        $query = User::query();

        switch ($data['recipient_type']) {
            case 'all_users':
                // All users
                break;

            case 'verified_users':
                $query->whereNotNull('email_verified_at');
                break;

            case 'subscribed_users':
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'active');
                });
                break;

            case 'trial_users':
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'trialing');
                });
                break;

            case 'specific_users':
                if (!empty($data['user_ids'])) {
                    $query->whereIn('id', $data['user_ids']);
                }
                break;
        }

        return $query->get();
    }

    protected function scheduleEmail(array $data, $users)
    {
        $scheduled = 0;

        foreach ($users as $user) {
            try {
                \App\Models\AdminEmailLog::create([
                    'admin_email_campaign_id' => null, // Manual send
                    'user_id' => $user->id,
                    'recipient_email' => $user->email,
                    'recipient_name' => $user->name,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'status' => 'scheduled',
                    'metadata' => [
                        'scheduled_at' => $data['scheduled_at'],
                        'from_name' => $data['from_name'] ?? null,
                        'from_email' => $data['from_email'] ?? null,
                        'reply_to' => $data['reply_to'] ?? null,
                        'attachments' => $data['attachments'] ?? null,
                    ],
                ]);

                $scheduled++;
            } catch (\Exception $e) {
                \Log::error('Failed to schedule admin email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Notification::make()
            ->title('Emails Scheduled!')
            ->body("Scheduled {$scheduled} email(s) for " . \Carbon\Carbon::parse($data['scheduled_at'])->format('M d, Y \a\t g:i A'))
            ->success()
            ->send();

        // Reset form
        $this->form->fill([
            'recipient_type' => 'verified_users',
            'user_ids' => [],
            'template_id' => null,
            'subject' => '',
            'body' => '',
            'from_name' => config('mail.from.name'),
            'from_email' => config('mail.from.address'),
            'reply_to' => '',
            'send_now' => true,
            'scheduled_at' => null,
            'attachments' => [],
        ]);
    }

    public function showPreview(Get $get)
    {
        $subject = $get('subject');
        $body = $get('body');

        if (empty($subject) || empty($body)) {
            Notification::make()
                ->title('Missing Information')
                ->body('Please add subject and body before previewing.')
                ->warning()
                ->send();
            return;
        }

        // Get first user for preview
        $data = $this->form->getState();
        $users = $this->getTargetedUsers($data);
        $sampleUser = $users->first();

        if (!$sampleUser) {
            // Use a placeholder user if no users found
            $sampleUser = new User([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        }

        // Replace variables with sample user data
        $this->previewSubject = $this->replaceVariables($subject, $sampleUser);
        $this->previewBody = $this->replaceVariables($body, $sampleUser);
        $this->showPreviewModal = true;

        $this->dispatch('open-modal', id: 'email-preview');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send Email')
                ->modalDescription(function () {
                    $data = $this->form->getState();
                    $users = $this->getTargetedUsers($data);
                    $count = $users->count();

                    return "Are you sure you want to send this email to {$count} user(s)?";
                })
                ->modalSubmitActionLabel('Send')
                ->action(fn () => $this->sendEmail()),
        ];
    }
}
