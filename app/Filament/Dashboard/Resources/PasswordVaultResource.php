<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\PasswordVaultResource\Pages;
use App\Models\PasswordVault;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;


class PasswordVaultResource extends Resource
{
    protected static ?string $model = PasswordVault::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Password Vault';

    protected static ?string $modelLabel = 'Password';
    protected static UnitEnum|string|null $navigationGroup = 'Security';

    protected static ?string $pluralModelLabel = 'Password Vault';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Login Details')
                    ->description('Store your login credentials securely')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title / Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Gmail Account, Facebook, AWS Console')
                            ->helperText('A memorable name to identify this entry')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Website URL')
                            ->url()
                            ->prefix('https://')
                            ->placeholder('example.com')
                            ->maxLength(255)
                            ->helperText('The website or service URL'),

                        Forms\Components\Select::make('category')
                            ->options([
                                'social_media' => 'Social Media',
                                'email' => 'Email',
                                'banking' => 'Banking & Finance',
                                'work' => 'Work',
                                'personal' => 'Personal',
                                'entertainment' => 'Entertainment',
                                'shopping' => 'Shopping',
                                'development' => 'Development',
                                'other' => 'Other',
                            ])
                            ->native(false)
                            ->searchable()
                            ->placeholder('Select category'),
                    ])
                    ->columns(2),

                Section::make('Credentials')
                    ->description('Your login information (encrypted)')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->maxLength(255)
                            ->placeholder('Username or ID')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('user@example.com')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255)
                            ->helperText('Your password is encrypted using AES-256 encryption')
                            ->dehydrated()
                            ->autocomplete('off')
                            ->extraAttributes(['autocomplete' => 'new-password'])
                            ->default(function ($record) {
                                return $record ? $record->password : '';
                            })
                            ->suffixAction(
                                Action::make('generate')
                                    ->icon('heroicon-o-sparkles')
                                    ->action(function (Set $set) {
                                        $password = self::generateStrongPassword();
                                        $set('password', $password);

                                        Notification::make()
                                            ->title('Strong Password Generated')
                                            ->body('A secure password has been generated. Make sure to save it!')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->description('Optional notes and settings')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Any additional information or security questions...')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Mark as Favorite')
                            ->inline(false)
                            ->helperText('Favorite items appear at the top of your vault'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->action(function (PasswordVault $record) {
                        $record->update(['is_favorite' => !$record->is_favorite]);

                        Notification::make()
                            ->title($record->is_favorite ? 'Added to Favorites' : 'Removed from Favorites')
                            ->success()
                            ->send();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PasswordVault $record): ?string => $record->website_url),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (PasswordVault $record): string => $record->category_color)
                    ->icon(fn (PasswordVault $record): string => $record->category_icon)
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', Str::title($state)))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username/Email')
                    ->searchable()
                    ->formatStateUsing(function (PasswordVault $record): string {
                        return $record->username ?? $record->email ?? '—';
                    })
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('password_health')
                    ->label('Health')
                    ->badge()
                    ->color(fn (PasswordVault $record): string => $record->password_health_color)
                    ->formatStateUsing(fn (string $state): string => Str::upper(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->tooltip(fn (PasswordVault $record): string => match($record->password_health) {
                        'compromised' => 'Password compromised - update immediately!',
                        'update_needed' => 'Password needs to be updated',
                        'weak' => 'Weak password - consider strengthening',
                        'old' => 'Password is over 90 days old',
                        'fair' => 'Password could be stronger',
                        'good' => 'Password is healthy',
                        default => '',
                    })
                    ->visible(true),

                Tables\Columns\TextColumn::make('password_age_days')
                    ->label('Age')
                    ->formatStateUsing(fn (int $state): string => $state . ' days')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(true),

                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->label('Last Accessed')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never')
                    ->toggleable()
                    ->visible(true),

                Tables\Columns\TextColumn::make('access_count')
                    ->label('Access Count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_favorite', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'social_media' => 'Social Media',
                        'email' => 'Email',
                        'banking' => 'Banking & Finance',
                        'work' => 'Work',
                        'personal' => 'Personal',
                        'entertainment' => 'Entertainment',
                        'shopping' => 'Shopping',
                        'development' => 'Development',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favorites')
                    ->placeholder('All entries')
                    ->trueLabel('Favorites only')
                    ->falseLabel('Not favorites'),

                Tables\Filters\TernaryFilter::make('is_compromised')
                    ->label('Compromised')
                    ->placeholder('All passwords')
                    ->trueLabel('Compromised only')
                    ->falseLabel('Not compromised'),

                Tables\Filters\TernaryFilter::make('needs_update')
                    ->label('Needs Update')
                    ->placeholder('All passwords')
                    ->trueLabel('Needs update')
                    ->falseLabel('Up to date'),

                Tables\Filters\SelectFilter::make('password_health')
                    ->label('Password Health')
                    ->options([
                        'compromised' => 'Compromised',
                        'update_needed' => 'Update Needed',
                        'weak' => 'Weak',
                        'old' => 'Old (90+ days)',
                        'fair' => 'Fair',
                        'good' => 'Good',
                    ])
                    ->attribute('password_health'),
            ])
            ->actions([
                Action::make('view_password')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn (PasswordVault $record) => $record->title)
                    ->modalDescription('Your password is displayed below')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (PasswordVault $record) => view('filament.dashboard.resources.password-vault-resource.view-password', [
                        'record' => $record,
                    ]))
                    ->action(function (PasswordVault $record) {
                        $record->trackAccess();
                    }),

                EditAction::make()
                    ->color('gray'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No passwords saved yet')
            ->emptyStateDescription('Start securing your passwords by adding your first entry')
            ->emptyStateIcon('heroicon-o-lock-closed')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Add Your First Password')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasswordVaults::route('/'),
            'create' => Pages\CreatePasswordVault::route('/create'),
            'edit' => Pages\EditPasswordVault::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreate(\App\Models\PasswordVault::class, 'passwords_limit') ?? false;
    }

    /**
     * Generate a strong random password
     */
    protected static function generateStrongPassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $allChars = $uppercase . $lowercase . $numbers . $special;

        // Ensure at least one character from each set
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())->count();
    }

}
