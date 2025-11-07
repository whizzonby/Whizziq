<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\BusinessProfile;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Page;
use Saasykit\FilamentOnboarding\Pages\OnboardingPage;

class AdvancedOnboardingPage extends OnboardingPage
{
    // Business Profile & Registration Data
    public string $biz_registered_name = '';
    public string $biz_trading_name = '';
    public string $biz_country = '';
    public string $biz_tax_id = '';
    public string $biz_incorporation_date = '';
    public string $biz_legal_type = '';

    // Business Operations Snapshot
    public int $ops_employee_count = 0;
    public string $ops_location = '';
    public string $ops_hours = '';
    public array $ops_systems = [];

    // Revenue & Sales Channels
    public float $rev_monthly_avg = 0;
    public float $rev_yoy_change = 0;
    public array $rev_payment_methods = [];
    public array $rev_channels = [];
    public array $rev_top_customers = [];

    // Expense & Cost Structure
    public float $exp_fixed_monthly = 0;
    public float $exp_variable_monthly = 0;
    public float $exp_payroll = 0;
    public float $exp_marketing = 0;
    public float $exp_loans = 0;

    // Human Resources Snapshot
    public int $hr_full_time = 0;
    public int $hr_part_time = 0;
    public float $hr_avg_salary = 0;
    public array $hr_roles = [];
    public string $hr_contractors = '';

    // Marketing & Digital Presence
    public array $mkt_platforms = [];
    public array $mkt_followers = [];
    public float $mkt_budget = 0;
    public ?float $mkt_traffic = null;
    public ?float $mkt_bounce_rate = null;

    // Systems & Compliance
    public string $comp_tax_cycle = '';
    public array $comp_licenses = [];
    public string $comp_bookkeeping_type = '';
    public string $comp_accountant_name = '';

    // Financial Health Indicators
    public ?int $fin_ar_days = null;
    public ?int $fin_ap_days = null;
    public ?float $fin_bank_balance = null;
    public ?float $fin_debt_amount = null;

    // Strategy & Growth Plans
    public array $strat_goals = [];
    public array $strat_investments = [];
    public array $strat_challenges = [];

    // Owner Preferences & AI Settings
    public string $prefs_insight_freq = 'weekly';
    public string $prefs_report_format = 'interactive';
    public string $prefs_detail_level = 'basic';
    public bool $prefs_ai_actions = true;

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Advanced Business Onboarding')
                ->tabs([
                    // Tab 1: Business Profile & Registration
                    Tab::make('Business Profile')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([
                            Section::make('Registration Details')
                                ->schema([
                                    TextInput::make('biz_registered_name')
                                        ->label('Registered business name')
                                        ->required()
                                        ->maxLength(80),
                                    
                                    TextInput::make('biz_trading_name')
                                        ->label('Trading name (if different)')
                                        ->maxLength(80),
                                    
                                    Select::make('biz_country')
                                        ->label('Country of registration')
                                        ->options([
                                            'US' => 'United States',
                                            'CA' => 'Canada',
                                            'GB' => 'United Kingdom',
                                            'AU' => 'Australia',
                                            'DE' => 'Germany',
                                            'FR' => 'France',
                                            'IT' => 'Italy',
                                            'ES' => 'Spain',
                                            'NL' => 'Netherlands',
                                            'BE' => 'Belgium',
                                            'CH' => 'Switzerland',
                                            'AT' => 'Austria',
                                            'SE' => 'Sweden',
                                            'NO' => 'Norway',
                                            'DK' => 'Denmark',
                                            'FI' => 'Finland',
                                            'IE' => 'Ireland',
                                            'PT' => 'Portugal',
                                            'LU' => 'Luxembourg',
                                            'MT' => 'Malta',
                                            'CY' => 'Cyprus',
                                            'EE' => 'Estonia',
                                            'LV' => 'Latvia',
                                            'LT' => 'Lithuania',
                                            'PL' => 'Poland',
                                            'CZ' => 'Czech Republic',
                                            'SK' => 'Slovakia',
                                            'HU' => 'Hungary',
                                            'SI' => 'Slovenia',
                                            'HR' => 'Croatia',
                                            'BG' => 'Bulgaria',
                                            'RO' => 'Romania',
                                            'GR' => 'Greece',
                                            'JP' => 'Japan',
                                            'KR' => 'South Korea',
                                            'SG' => 'Singapore',
                                            'HK' => 'Hong Kong',
                                            'TW' => 'Taiwan',
                                            'MY' => 'Malaysia',
                                            'TH' => 'Thailand',
                                            'ID' => 'Indonesia',
                                            'PH' => 'Philippines',
                                            'VN' => 'Vietnam',
                                            'IN' => 'India',
                                            'CN' => 'China',
                                            'BR' => 'Brazil',
                                            'MX' => 'Mexico',
                                            'AR' => 'Argentina',
                                            'CL' => 'Chile',
                                            'CO' => 'Colombia',
                                            'PE' => 'Peru',
                                            'UY' => 'Uruguay',
                                            'ZA' => 'South Africa',
                                            'NG' => 'Nigeria',
                                            'KE' => 'Kenya',
                                            'EG' => 'Egypt',
                                            'MA' => 'Morocco',
                                            'TN' => 'Tunisia',
                                            'DZ' => 'Algeria',
                                            'LY' => 'Libya',
                                            'SD' => 'Sudan',
                                            'ET' => 'Ethiopia',
                                            'GH' => 'Ghana',
                                            'UG' => 'Uganda',
                                            'TZ' => 'Tanzania',
                                            'ZM' => 'Zambia',
                                            'ZW' => 'Zimbabwe',
                                            'BW' => 'Botswana',
                                            'NA' => 'Namibia',
                                            'SZ' => 'Swaziland',
                                            'LS' => 'Lesotho',
                                            'MW' => 'Malawi',
                                            'MZ' => 'Mozambique',
                                            'MG' => 'Madagascar',
                                            'MU' => 'Mauritius',
                                            'SC' => 'Seychelles',
                                            'KM' => 'Comoros',
                                            'DJ' => 'Djibouti',
                                            'SO' => 'Somalia',
                                            'ER' => 'Eritrea',
                                            'SS' => 'South Sudan',
                                            'CF' => 'Central African Republic',
                                            'TD' => 'Chad',
                                            'NE' => 'Niger',
                                            'ML' => 'Mali',
                                            'BF' => 'Burkina Faso',
                                            'CI' => 'Ivory Coast',
                                            'LR' => 'Liberia',
                                            'SL' => 'Sierra Leone',
                                            'GN' => 'Guinea',
                                            'GW' => 'Guinea-Bissau',
                                            'GM' => 'Gambia',
                                            'SN' => 'Senegal',
                                            'MR' => 'Mauritania',
                                            'CV' => 'Cape Verde',
                                            'ST' => 'São Tomé and Príncipe',
                                            'GQ' => 'Equatorial Guinea',
                                            'GA' => 'Gabon',
                                            'CG' => 'Republic of the Congo',
                                            'CD' => 'Democratic Republic of the Congo',
                                            'AO' => 'Angola',
                                            'CM' => 'Cameroon',
                                            'CF' => 'Central African Republic',
                                            'TD' => 'Chad',
                                            'NE' => 'Niger',
                                            'ML' => 'Mali',
                                            'BF' => 'Burkina Faso',
                                            'CI' => 'Ivory Coast',
                                            'LR' => 'Liberia',
                                            'SL' => 'Sierra Leone',
                                            'GN' => 'Guinea',
                                            'GW' => 'Guinea-Bissau',
                                            'GM' => 'Gambia',
                                            'SN' => 'Senegal',
                                            'MR' => 'Mauritania',
                                            'CV' => 'Cape Verde',
                                            'ST' => 'São Tomé and Príncipe',
                                            'GQ' => 'Equatorial Guinea',
                                            'GA' => 'Gabon',
                                            'CG' => 'Republic of the Congo',
                                            'CD' => 'Democratic Republic of the Congo',
                                            'AO' => 'Angola',
                                            'CM' => 'Cameroon',
                                        ])
                                        ->required()
                                        ->searchable(),
                                    
                                    TextInput::make('biz_tax_id')
                                        ->label('Registration ID / Tax Number')
                                        ->maxLength(50),
                                    
                                    DatePicker::make('biz_incorporation_date')
                                        ->label('Date of registration')
                                        ->required()
                                        ->maxDate(now()),
                                    
                                    Select::make('biz_legal_type')
                                        ->label('Business structure')
                                        ->options([
                                            'sole_trader' => 'Sole Trader',
                                            'partnership' => 'Partnership',
                                            'llc' => 'Limited Liability Company',
                                            'cooperative' => 'Co-operative',
                                            'other' => 'Other',
                                        ])
                                        ->required(),
                                ]),
                        ]),

                    // Tab 2: Business Operations Snapshot
                    Tab::make('Operations')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('Operations Overview')
                                ->schema([
                                    TextInput::make('ops_employee_count')
                                        ->label('Current number of employees')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),
                                    
                                    TextInput::make('ops_location')
                                        ->label('Primary location of operations')
                                        ->maxLength(200),
                                    
                                    TextInput::make('ops_hours')
                                        ->label('Typical operating hours')
                                        ->placeholder('e.g., 9 AM - 5 PM, Monday-Friday'),
                                    
                                    CheckboxList::make('ops_systems')
                                        ->label('Business systems used')
                                        ->options([
                                            'quickbooks' => 'QuickBooks',
                                            'xero' => 'Xero',
                                            'shopify' => 'Shopify',
                                            'zoho' => 'Zoho',
                                            'custom' => 'Custom System',
                                            'none' => 'None',
                                        ])
                                        ->columns(2),
                                ]),
                        ]),

                    // Tab 3: Revenue & Sales Channels
                    Tab::make('Revenue & Sales')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Section::make('Revenue Overview')
                                ->schema([
                                    TextInput::make('rev_monthly_avg')
                                        ->label('Average monthly sales revenue')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required(),
                                    
                                    TextInput::make('rev_yoy_change')
                                        ->label('Sales trend vs last year (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->helperText('Positive for growth, negative for decline'),
                                    
                                    CheckboxList::make('rev_payment_methods')
                                        ->label('Payment methods accepted')
                                        ->options([
                                            'cash' => 'Cash',
                                            'debit_credit' => 'Debit/Credit Card',
                                            'mobile_wallet' => 'Mobile Wallet',
                                            'bank_transfer' => 'Bank Transfer',
                                            'online_checkout' => 'Online Checkout',
                                        ])
                                        ->columns(2),
                                    
                                    CheckboxList::make('rev_channels')
                                        ->label('Primary sales channels')
                                        ->options([
                                            'in_store' => 'In-store',
                                            'online_website' => 'Online Website',
                                            'wholesale' => 'Wholesale',
                                            'marketplace' => 'Third-party Marketplace',
                                            'social_media' => 'Social Media',
                                            'subscription' => 'Subscription',
                                            'direct_sales' => 'Direct Sales',
                                        ])
                                        ->required()
                                        ->columns(2),
                                    
                                    Repeater::make('rev_top_customers')
                                        ->label('Top 3 customers by value (optional)')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Customer Name')
                                                ->required(),
                                            TextInput::make('value')
                                                ->label('Monthly Value')
                                                ->numeric()
                                                ->prefix('$'),
                                        ])
                                        ->defaultItems(0)
                                        ->maxItems(3)
                                        ->collapsible(),
                                ]),
                        ]),

                    // Tab 4: Expense & Cost Structure
                    Tab::make('Expenses')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Monthly Expenses')
                                ->schema([
                                    TextInput::make('exp_fixed_monthly')
                                        ->label('Monthly fixed costs (rent, utilities)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                    
                                    TextInput::make('exp_variable_monthly')
                                        ->label('Variable costs (COGS, fuel, materials)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                    
                                    TextInput::make('exp_payroll')
                                        ->label('Payroll (total)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                    
                                    TextInput::make('exp_marketing')
                                        ->label('Marketing spend')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                    
                                    TextInput::make('exp_loans')
                                        ->label('Loan repayments (if any)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                ]),
                        ]),

                    // Tab 5: Human Resources Snapshot
                    Tab::make('Human Resources')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Section::make('Team Overview')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('hr_full_time')
                                                ->label('Number of full-time employees')
                                                ->numeric()
                                                ->default(0),
                                            
                                            TextInput::make('hr_part_time')
                                                ->label('Number of part-time employees')
                                                ->numeric()
                                                ->default(0),
                                        ]),
                                    
                                    TextInput::make('hr_avg_salary')
                                        ->label('Average monthly salary')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                    
                                    Repeater::make('hr_roles')
                                        ->label('Staff roles and departments')
                                        ->schema([
                                            TextInput::make('role')
                                                ->label('Role/Department')
                                                ->required(),
                                        ])
                                        ->defaultItems(0)
                                        ->collapsible(),
                                    
                                    Textarea::make('hr_contractors')
                                        ->label('Any contractors / outsourced services?')
                                        ->rows(3)
                                        ->placeholder('Describe any outsourced services or contractors'),
                                ]),
                        ]),

                    // Tab 6: Marketing & Digital Presence
                    Tab::make('Marketing')
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            Section::make('Digital Presence')
                                ->schema([
                                    CheckboxList::make('mkt_platforms')
                                        ->label('Active social platforms')
                                        ->options([
                                            'instagram' => 'Instagram',
                                            'facebook' => 'Facebook',
                                            'linkedin' => 'LinkedIn',
                                            'tiktok' => 'TikTok',
                                            'youtube' => 'YouTube',
                                            'other' => 'Other',
                                        ])
                                        ->columns(2),
                                    
                                    Repeater::make('mkt_followers')
                                        ->label('Follower counts (per platform)')
                                        ->schema([
                                            TextInput::make('platform')
                                                ->label('Platform')
                                                ->required(),
                                            TextInput::make('count')
                                                ->label('Follower Count')
                                                ->numeric()
                                                ->required(),
                                        ])
                                        ->defaultItems(0)
                                        ->collapsible(),
                                    
                                    TextInput::make('mkt_budget')
                                        ->label('Ad budget per month')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0),
                                    
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('mkt_traffic')
                                                ->label('Website traffic (monthly)')
                                                ->numeric(),
                                            
                                            TextInput::make('mkt_bounce_rate')
                                                ->label('Bounce rate (%)')
                                                ->numeric()
                                                ->suffix('%'),
                                        ]),
                                ]),
                        ]),

                    // Tab 7: Systems & Compliance
                    Tab::make('Compliance')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Section::make('Compliance & Systems')
                                ->schema([
                                    Select::make('comp_tax_cycle')
                                        ->label('Do you file taxes monthly / quarterly / annually?')
                                        ->options([
                                            'monthly' => 'Monthly',
                                            'quarterly' => 'Quarterly',
                                            'annually' => 'Annually',
                                        ])
                                        ->required(),
                                    
                                    Repeater::make('comp_licenses')
                                        ->label('Any recent licenses or renewals?')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('License Name')
                                                ->required(),
                                            DatePicker::make('expiry_date')
                                                ->label('Expiry Date')
                                                ->required(),
                                        ])
                                        ->defaultItems(0)
                                        ->collapsible(),
                                    
                                    Select::make('comp_bookkeeping_type')
                                        ->label('Bookkeeping method')
                                        ->options([
                                            'manual' => 'Manual',
                                            'spreadsheet' => 'Spreadsheet',
                                            'accounting_software' => 'Accounting Software (QB/Xero)',
                                            'outsourced' => 'Outsourced',
                                        ]),
                                    
                                    TextInput::make('comp_accountant_name')
                                        ->label('Auditor / Accountant details (optional)')
                                        ->maxLength(200),
                                ]),
                        ]),

                    // Tab 8: Financial Health Indicators
                    Tab::make('Financial Health')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Section::make('Financial Indicators')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('fin_ar_days')
                                                ->label('Average accounts receivable (days)')
                                                ->numeric(),
                                            
                                            TextInput::make('fin_ap_days')
                                                ->label('Average accounts payable (days)')
                                                ->numeric(),
                                        ]),
                                    
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('fin_bank_balance')
                                                ->label('Current bank balance (approx.)')
                                                ->numeric()
                                                ->prefix('$'),
                                            
                                            TextInput::make('fin_debt_amount')
                                                ->label('Outstanding debts or loans')
                                                ->numeric()
                                                ->prefix('$'),
                                        ]),
                                ]),
                        ]),

                    // Tab 9: Strategy & Growth Plans
                    Tab::make('Strategy')
                        ->icon('heroicon-o-light-bulb')
                        ->schema([
                            Section::make('Growth Plans')
                                ->schema([
                                    Repeater::make('strat_goals')
                                        ->label('Top 3 goals for next 12 months')
                                        ->schema([
                                            Textarea::make('goal')
                                                ->label('Goal')
                                                ->required()
                                                ->rows(2),
                                        ])
                                        ->defaultItems(0)
                                        ->maxItems(3)
                                        ->collapsible(),
                                    
                                    Repeater::make('strat_investments')
                                        ->label('Planned investments / equipment purchase')
                                        ->schema([
                                            TextInput::make('description')
                                                ->label('Investment Description')
                                                ->required(),
                                            TextInput::make('value')
                                                ->label('Value')
                                                ->numeric()
                                                ->prefix('$'),
                                        ])
                                        ->defaultItems(0)
                                        ->collapsible(),
                                    
                                    Repeater::make('strat_challenges')
                                        ->label('Biggest challenges')
                                        ->schema([
                                            Textarea::make('challenge')
                                                ->label('Challenge')
                                                ->required()
                                                ->rows(2),
                                        ])
                                        ->defaultItems(0)
                                        ->collapsible(),
                                ]),
                        ]),

                    // Tab 10: Owner Preferences & AI Settings
                    Tab::make('Preferences')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('AI Settings & Preferences')
                                ->schema([
                                    Select::make('prefs_insight_freq')
                                        ->label('Insight frequency')
                                        ->options([
                                            'daily' => 'Daily',
                                            'weekly' => 'Weekly',
                                            'monthly' => 'Monthly',
                                        ])
                                        ->required(),
                                    
                                    Select::make('prefs_report_format')
                                        ->label('Preferred report format')
                                        ->options([
                                            'pdf' => 'PDF Download',
                                            'email' => 'Email Summary',
                                            'interactive' => 'Interactive Dashboard',
                                        ])
                                        ->required(),
                                    
                                    Select::make('prefs_detail_level')
                                        ->label('Level of detail desired')
                                        ->options([
                                            'basic' => 'Basic',
                                            'advanced' => 'Advanced',
                                        ])
                                        ->required(),
                                    
                                    Toggle::make('prefs_ai_actions')
                                        ->label('Allow AI to recommend actions')
                                        ->default(true),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    public function submit()
    {
        $this->validate();

        // Create business profile record
        $businessProfile = BusinessProfile::create([
            'user_id' => auth()->id(),
            'biz_registered_name' => $this->biz_registered_name,
            'biz_trading_name' => $this->biz_trading_name,
            'biz_country' => $this->biz_country,
            'biz_tax_id' => $this->biz_tax_id,
            'biz_incorporation_date' => $this->biz_incorporation_date,
            'biz_legal_type' => $this->biz_legal_type,
            'ops_employee_count' => $this->ops_employee_count,
            'ops_location' => $this->ops_location,
            'ops_hours' => $this->ops_hours,
            'ops_systems' => $this->ops_systems,
            'rev_monthly_avg' => $this->rev_monthly_avg,
            'rev_yoy_change' => $this->rev_yoy_change,
            'rev_payment_methods' => $this->rev_payment_methods,
            'rev_channels' => $this->rev_channels,
            'rev_top_customers' => $this->rev_top_customers,
            'exp_fixed_monthly' => $this->exp_fixed_monthly,
            'exp_variable_monthly' => $this->exp_variable_monthly,
            'exp_payroll' => $this->exp_payroll,
            'exp_marketing' => $this->exp_marketing,
            'exp_loans' => $this->exp_loans,
            'hr_full_time' => $this->hr_full_time,
            'hr_part_time' => $this->hr_part_time,
            'hr_avg_salary' => $this->hr_avg_salary,
            'hr_roles' => $this->hr_roles,
            'hr_contractors' => $this->hr_contractors,
            'mkt_platforms' => $this->mkt_platforms,
            'mkt_followers' => $this->mkt_followers,
            'mkt_budget' => $this->mkt_budget,
            'mkt_traffic' => $this->mkt_traffic,
            'mkt_bounce_rate' => $this->mkt_bounce_rate,
            'comp_tax_cycle' => $this->comp_tax_cycle,
            'comp_licenses' => $this->comp_licenses,
            'comp_bookkeeping_type' => $this->comp_bookkeeping_type,
            'comp_accountant_name' => $this->comp_accountant_name,
            'fin_ar_days' => $this->fin_ar_days,
            'fin_ap_days' => $this->fin_ap_days,
            'fin_bank_balance' => $this->fin_bank_balance,
            'fin_debt_amount' => $this->fin_debt_amount,
            'strat_goals' => $this->strat_goals,
            'strat_investments' => $this->strat_investments,
            'strat_challenges' => $this->strat_challenges,
            'prefs_insight_freq' => $this->prefs_insight_freq,
            'prefs_report_format' => $this->prefs_report_format,
            'prefs_detail_level' => $this->prefs_detail_level,
            'prefs_ai_actions' => $this->prefs_ai_actions,
        ]);

        // Calculate metrics
        $businessProfile->calculateMetrics();

        $this->onboarded();   // Mark the user as onboarded
    }
}
