<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\TaxSetting;
use App\Services\CountriesService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class TaxSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Tax Settings';
    protected static UnitEnum|string|null $navigationGroup = 'Tax & Compliance';

    protected static ?string $title = 'Tax Settings';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.dashboard.pages.tax-settings-page';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $taxSetting = $user->taxSetting ?? TaxSetting::firstOrCreate(['user_id' => $user->id]);

        $this->form->fill([
            'business_name' => $taxSetting->business_name,
            'tax_id' => $taxSetting->tax_id,
            'business_type' => $taxSetting->business_type,
            'country' => $taxSetting->country,
            'state' => $taxSetting->state,
            'fiscal_year_end' => $taxSetting->fiscal_year_end,
            'filing_frequency' => $taxSetting->filing_frequency,
            'tax_rate' => $taxSetting->tax_rate,
            'auto_categorize' => $taxSetting->auto_categorize,
            'reminder_enabled' => $taxSetting->reminder_enabled,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Business Information')
                    ->description('Configure your business details for tax reporting')
                    ->schema([
                        TextInput::make('business_name')
                            ->label('Business Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('tax_id')
                            ->label('Tax ID / EIN / VAT Number')
                            ->helperText('Enter your business tax identification number')
                            ->maxLength(50),

                        Select::make('business_type')
                            ->label('Business Type')
                            ->options([
                                'sole_proprietor' => 'Sole Proprietor',
                                'llc' => 'LLC',
                                's_corp' => 'S Corporation',
                                'c_corp' => 'C Corporation',
                                'partnership' => 'Partnership',
                            ])
                            ->default('sole_proprietor')
                            ->required(),

                        Select::make('country')
                            ->label('Country')
                            ->options(CountriesService::getAllCountries())
                            ->default('US')
                            ->required()
                            ->searchable()
                            ->placeholder('Select a country')
                            ->helperText('Search by country name or select from the list'),

                        TextInput::make('state')
                            ->label('State / Province')
                            ->maxLength(50),
                    ])->columns(2),

                Section::make('Tax Configuration')
                    ->description('Set your tax year and filing preferences')
                    ->schema([
                        DatePicker::make('fiscal_year_end')
                            ->label('Fiscal Year End Date')
                            ->helperText('When does your fiscal year end? (e.g., December 31)')
                            ->required()
                            ->displayFormat('M d, Y')
                            ->default(now()->endOfYear()),

                        Select::make('filing_frequency')
                            ->label('Filing Frequency')
                            ->options([
                                'quarterly' => 'Quarterly',
                                'annual' => 'Annual',
                            ])
                            ->default('annual')
                            ->required(),

                        TextInput::make('tax_rate')
                            ->label('Estimated Tax Rate (%)')
                            ->helperText('Your estimated effective tax rate for calculations')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(25.00),
                    ])->columns(3),

                Section::make('Automation')
                    ->description('Configure automatic tax features')
                    ->schema([
                        Toggle::make('auto_categorize')
                            ->label('Auto-Categorize Expenses')
                            ->helperText('Automatically suggest tax categories for expenses based on their description')
                            ->default(true),

                        Toggle::make('reminder_enabled')
                            ->label('Filing Deadline Reminders')
                            ->helperText('Send email reminders before tax filing deadlines')
                            ->default(true),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        TaxSetting::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        Notification::make()
            ->title('Tax settings saved successfully')
            ->success()
            ->send();
    }


}
