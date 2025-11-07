<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\FinanceResource\Pages;
use Filament\Resources\Resource;
use UnitEnum;
use BackedEnum;

class FinanceResource extends Resource
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Finance';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Financial Data';

    protected static ?string $pluralModelLabel = 'Finance';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFinance::route('/'),
        ];
    }

    // No model needed - this is a management page
    public static function canCreate(): bool
    {
        return false;
    }
}
