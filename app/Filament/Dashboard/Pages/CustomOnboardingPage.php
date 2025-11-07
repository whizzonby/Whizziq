<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\OnboardingData;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\RawJs;
use Saasykit\FilamentOnboarding\Pages\OnboardingPage;

class CustomOnboardingPage extends OnboardingPage
{
    // --- properties (unchanged) ---
    public string $user_name = '';
    public string $user_city = '';
    public string $user_country = '';
    public string $founder_stage = '';

    public string $biz_name = '';
    public string $biz_type = '';
    public string $industry_raw = '';
    public string $industry_code = '';
    public string $mission_text = '';
    public string $biz_stage = '';

    public array $items = [];

    public float $rent = 0;
    public float $utilities_software = 0;
    public float $marketing = 0;
    public float $staff = 0;
    public float $setup_one_time = 0;
    public float $total_available = 0;

    public ?float $expected_monthly_income = null;
    public string $payment_terms = '';
    public ?int $expected_breakeven_month = null;
    public ?string $capital_source = null;

    public array $marketing_channels = [];
    public array $social_handles = [];
    public string $website_url = '';
    public string $audience_type = '';
    public ?string $audience_age = null;

    public string $team_mode = '';
    public int $team_size = 0;
    public array $team_roles = [];

    public int $finance_skill = 3;
    public string $ai_tone = 'friendly';
    public string $insight_frequency = 'weekly';
    public bool $auto_email_reports = false;

    // --- form schema ---
    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                    // Step 1
                    Step::make('Personal Information')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Section::make('Your Profile')
                                ->schema([
                                    TextInput::make('user_name')
                                        ->label('Full Name')
                                        ->required(),
                                    Grid::make(2)->schema([
                                        TextInput::make('user_city')->label('City'),
                                        TextInput::make('user_country')->label('Country'),
                                    ]),
                                    Select::make('founder_stage')
                                        ->label('What best describes you?')
                                        ->options([
                                            'solo' => 'Solo founder',
                                            'freelancer' => 'Freelancer',
                                            'small_team' => 'Small team (2–5)',
                                            'growing_team' => 'Growing team (6–20)',
                                            'established_business' => 'Established business (20+)',
                                            'exploring' => 'Exploring an idea',
                                        ])
                                        ->required(),
                                ]),
                        ]),

                    // Step 2
                    Step::make('Business Identity')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([
                            Section::make('Business Details')
                                ->schema([
                                    TextInput::make('biz_name')
                                        ->label('Business or idea name')
                                        ->required(),
                                    Select::make('biz_type')
                                        ->label('Which best fits your idea?')
                                        ->options([
                                            'product' => 'Product-based',
                                            'service' => 'Service-based',
                                            'mixed' => 'Mixed',
                                        ])
                                        ->required(),
                                    TextInput::make('industry_raw')
                                        ->label('Industry/Niche')
                                        ->placeholder('e.g., E-commerce, SaaS, Consulting'),
                                    Textarea::make('mission_text')
                                        ->label('Mission')
                                        ->rows(2),
                                    Select::make('biz_stage')
                                        ->label('Business stage')
                                        ->options([
                                            'idea' => 'Idea',
                                            'testing' => 'Testing',
                                            'launching' => 'Launching soon',
                                        ])
                                        ->required(),
                                ]),
                        ]),

                    // Step 3
                    Step::make('Products & Services')
                        ->icon('heroicon-o-archive-box')
                        ->schema([
                            Section::make('Your Offerings')
                                ->schema([
                                    Repeater::make('items')
                                        ->label('Products/Services')
                                        ->schema([
                                            TextInput::make('name')->label('Name')->required(),
                                            Grid::make(2)->schema([
                                                TextInput::make('price')
                                                    ->label('Price')
                                                    ->numeric()
                                                    ->prefix('$'),
                                                TextInput::make('cost')
                                                    ->label('Cost')
                                                    ->numeric()
                                                    ->prefix('$'),
                                            ]),
                                            TextInput::make('units_per_month')
                                                ->numeric()
                                                ->label('Expected monthly sales'),
                                            Select::make('sale_type')
                                                ->label('Sale type')
                                                ->options([
                                                    'one_time' => 'One-time',
                                                    'monthly' => 'Subscription (monthly)',
                                                    'annual' => 'Subscription (annual)',
                                                    'package' => 'Package/Bundled',
                                                ]),
                                        ])
                                        ->defaultItems(1)
                                        ->maxItems(3),
                                ]),
                        ]),

                    // Step 4
                    Step::make('Cost Structure')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Monthly Costs')->schema([
                                TextInput::make('rent')->numeric()->prefix('$')->label('Rent/workspace'),
                                TextInput::make('utilities_software')->numeric()->prefix('$')->label('Utilities/software'),
                                TextInput::make('marketing')->numeric()->prefix('$')->label('Marketing'),
                                TextInput::make('staff')->numeric()->prefix('$')->label('Staff payments'),
                            ]),
                            Section::make('Startup Capital')->schema([
                                TextInput::make('setup_one_time')->numeric()->prefix('$')->label('One-time setup cost'),
                                TextInput::make('total_available')->numeric()->prefix('$')->label('Available capital'),
                            ]),
                        ]),

                    // Step 5
                    Step::make('Revenue & Cashflow')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Section::make('Revenue Expectations')
                                ->schema([
                                    TextInput::make('expected_monthly_income')->numeric()->prefix('$')->label('Expected monthly income'),
                                    Select::make('payment_terms')->label('Payment timing')->options([
                                        'immediate' => 'Immediate',
                                        '7_days' => '7 days',
                                        '30_days' => '30 days',
                                        '60_days' => '60 days',
                                    ]),
                                    TextInput::make('expected_breakeven_month')
                                        ->numeric()
                                        ->suffix('months')
                                        ->label('Expected break-even period'),
                                    Select::make('capital_source')
                                        ->label('Funding source')
                                        ->options([
                                            'personal' => 'Personal',
                                            'friends_family' => 'Friends & Family',
                                            'grant' => 'Grant',
                                            'loan' => 'Loan',
                                            'investor' => 'Investor',
                                            'none' => 'None yet',
                                        ]),
                                ]),
                        ]),

                    // Step 6
                    Step::make('Marketing & Reach')
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            Section::make('Marketing Channels')->schema([
                                CheckboxList::make('marketing_channels')
                                    ->columns(2)
                                    ->options([
                                        'instagram' => 'Instagram',
                                        'facebook' => 'Facebook',
                                        'tiktok' => 'TikTok',
                                        'linkedin' => 'LinkedIn',
                                        'youtube' => 'YouTube',
                                        'whatsapp' => 'WhatsApp',
                                        'email' => 'Email',
                                        'word_of_mouth' => 'Word-of-mouth',
                                        'walk_ins' => 'Walk-ins',
                                        'website_seo' => 'Website/SEO',
                                        'marketplaces' => 'Marketplaces',
                                        'b2b_outreach' => 'B2B outreach',
                                    ]),
                                Repeater::make('social_handles')
                                    ->schema([
                                        TextInput::make('platform')->label('Platform'),
                                        TextInput::make('handle')->label('Handle/URL'),
                                    ]),
                                TextInput::make('website_url')->url()->label('Website'),
                            ]),
                            Section::make('Target Audience')->schema([
                                Select::make('audience_type')
                                    ->label('Target audience')
                                    ->options([
                                        'b2c' => 'Consumers (B2C)',
                                        'b2b' => 'Businesses (B2B)',
                                        'both' => 'Both',
                                    ]),
                                Select::make('audience_age')
                                    ->label('Age group')
                                    ->options([
                                        '13-17' => '13–17',
                                        '18-24' => '18–24',
                                        '25-34' => '25–34',
                                        '35-44' => '35–44',
                                        '45-54' => '45–54',
                                        '55+' => '55+',
                                    ])
                                    ->visible(fn (Get $get) => in_array($get('audience_type'), ['b2c', 'both'])),
                            ]),
                        ]),

                    // Step 7
                    Step::make('Team & Support')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Section::make('Team Information')->schema([
                                Select::make('team_mode')
                                    ->label('Working style')
                                    ->options([
                                        'solo' => 'Solo',
                                        'small_team' => 'Small team (2–5)',
                                        'larger' => 'Larger (6+)',
                                    ]),
                                TextInput::make('team_size')
                                    ->numeric()
                                    ->visible(fn (Get $get) => $get('team_mode') !== 'solo'),
                                Repeater::make('team_roles')
                                    ->schema([
                                        TextInput::make('role')->label('Role'),
                                    ]),
                            ]),
                        ]),

                    // Step 8
                    Step::make('Preferences')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('Your Preferences')->schema([
                                Slider::make('finance_skill')
                                    ->label('Comfort with finances')
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->default(3)
                                    ->step(1)
                                    ->pips()
                                    ->pipsValues([1, 3, 5])
                                    ->pipsFormatter(RawJs::make('function(value) { 
                                        const labels = {1: "Beginner", 3: "Intermediate", 5: "Expert"}; 
                                        return labels[value] || value; 
                                    }')),
                                Select::make('ai_tone')->label('Preferred tone')->options([
                                    'friendly' => 'Friendly',
                                    'professional' => 'Professional',
                                    'analytical' => 'Analytical',
                                ]),
                                Select::make('insight_frequency')->label('Insight frequency')->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                ]),
                                Toggle::make('auto_email_reports')->label('Send starter projections by email'),
                            ]),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    public function submit()
    {
        $this->validate();

        $onboardingData = OnboardingData::create([
            'user_id' => auth()->id(),
            'user_name' => $this->user_name,
            'user_city' => $this->user_city,
            'user_country' => $this->user_country,
            'founder_stage' => $this->founder_stage,
            'biz_name' => $this->biz_name,
            'biz_type' => $this->biz_type,
            'industry_raw' => $this->industry_raw,
            'industry_code' => $this->industry_code,
            'mission_text' => $this->mission_text,
            'biz_stage' => $this->biz_stage,
            'items' => $this->items,
            'rent' => $this->rent,
            'utilities_software' => $this->utilities_software,
            'marketing' => $this->marketing,
            'staff' => $this->staff,
            'setup_one_time' => $this->setup_one_time,
            'total_available' => $this->total_available,
            'expected_monthly_income' => $this->expected_monthly_income,
            'payment_terms' => $this->payment_terms,
            'expected_breakeven_month' => $this->expected_breakeven_month,
            'capital_source' => $this->capital_source,
            'marketing_channels' => $this->marketing_channels,
            'social_handles' => $this->social_handles,
            'website_url' => $this->website_url,
            'audience_type' => $this->audience_type,
            'audience_age' => $this->audience_age,
            'team_mode' => $this->team_mode,
            'team_size' => $this->team_size,
            'team_roles' => $this->team_roles,
            'finance_skill' => $this->finance_skill,
            'ai_tone' => $this->ai_tone,
            'insight_frequency' => $this->insight_frequency,
            'auto_email_reports' => $this->auto_email_reports,
        ]);

        $onboardingData->calculateMetrics();
        $this->onboarded();
    }
}
