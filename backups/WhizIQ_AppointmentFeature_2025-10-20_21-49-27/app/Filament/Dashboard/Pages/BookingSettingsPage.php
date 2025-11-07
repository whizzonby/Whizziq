<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\BookingSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class BookingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Booking Settings';

    protected static ?string $title = 'Configure Your Booking Page';

    protected static UnitEnum|string|null $navigationGroup = 'Booking';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.dashboard.pages.booking-settings-page';

    public ?array $data = [];

    public ?BookingSetting $settings = null;

    public function mount(): void
    {
        $this->settings = BookingSetting::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'booking_slug' => $this->generateUniqueSlug(),
                'is_booking_enabled' => true,
                'display_name' => auth()->user()->name,
                'timezone' => config('app.timezone', 'UTC'),
                'brand_color' => '#3B82F6',
                'min_booking_notice_hours' => 24,
                'max_booking_days_ahead' => 60,
                'require_approval' => false,
            ]
        );

        $this->form->fill([
            'is_booking_enabled' => $this->settings->is_booking_enabled,
            'booking_slug' => $this->settings->booking_slug,
            'display_name' => $this->settings->display_name,
            'welcome_message' => $this->settings->welcome_message,
            'brand_color' => $this->settings->brand_color,
            'timezone' => $this->settings->timezone,
            'min_booking_notice_hours' => $this->settings->min_booking_notice_hours,
            'max_booking_days_ahead' => $this->settings->max_booking_days_ahead,
            'require_approval' => $this->settings->require_approval,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_booking_enabled')
                            ->label('Enable Public Booking')
                            ->helperText('Allow clients to book appointments through your public booking page')
                            ->default(true)
                            ->live(),

                        Forms\Components\TextInput::make('booking_slug')
                            ->label('Booking Page URL')
                            ->required()
                            ->unique(BookingSetting::class, 'booking_slug', ignoreRecord: true)
                            ->alphaDash()
                            ->maxLength(100)
                            ->prefix(url('/book/'))
                            ->helperText('This is your unique booking page URL that you can share with clients')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('booking_slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Your name or business name shown to clients'),

                        Forms\Components\Textarea::make('welcome_message')
                            ->label('Welcome Message')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('A friendly message shown on your booking page'),

                        Forms\Components\ColorPicker::make('brand_color')
                            ->label('Brand Color')
                            ->helperText('Primary color for your booking page')
                            ->default('#3B82F6'),
                    ])
                    ->columns(2),

                Section::make('Availability Settings')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->required()
                            ->searchable()
                            ->options(function () {
                                $timezones = [];
                                foreach (timezone_identifiers_list() as $timezone) {
                                    $timezones[$timezone] = $timezone;
                                }
                                return $timezones;
                            })
                            ->default(config('app.timezone', 'UTC'))
                            ->helperText('All appointment times will be shown in this timezone'),

                        Forms\Components\TextInput::make('min_booking_notice_hours')
                            ->label('Minimum Booking Notice (hours)')
                            ->required()
                            ->numeric()
                            ->default(24)
                            ->minValue(0)
                            ->maxValue(720)
                            ->helperText('How far in advance clients must book (e.g., 24 hours)'),

                        Forms\Components\TextInput::make('max_booking_days_ahead')
                            ->label('Maximum Booking Window (days)')
                            ->required()
                            ->numeric()
                            ->default(60)
                            ->minValue(1)
                            ->maxValue(365)
                            ->helperText('How far in advance clients can book (e.g., 60 days)'),

                        Forms\Components\Toggle::make('require_approval')
                            ->label('Require Approval')
                            ->helperText('All bookings require your manual approval before confirmation')
                            ->default(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->settings->update($data);

        Notification::make()
            ->title('Settings Saved')
            ->body('Your booking settings have been updated successfully.')
            ->success()
            ->send();
    }

    public function copyBookingUrl(): void
    {
        Notification::make()
            ->title('URL Copied')
            ->body('Your booking page URL has been copied to clipboard.')
            ->success()
            ->send();
    }

    public function getBookingUrl(): string
    {
        return $this->settings ? $this->settings->booking_url : '';
    }

    protected function generateUniqueSlug(): string
    {
        $slug = Str::slug(auth()->user()->name);
        $originalSlug = $slug;
        $counter = 1;

        while (BookingSetting::where('booking_slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewBookingPage')
                ->label('View Booking Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('primary')
                ->url(fn () => $this->getBookingUrl(), shouldOpenInNewTab: true)
                ->visible(fn () => $this->settings && $this->settings->is_booking_enabled),
        ];
    }
}
