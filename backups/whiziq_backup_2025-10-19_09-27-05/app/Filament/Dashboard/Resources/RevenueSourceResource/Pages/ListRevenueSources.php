<?php

namespace App\Filament\Dashboard\Resources\RevenueSourceResource\Pages;

use App\Filament\Dashboard\Resources\RevenueSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenueSources extends ListRecords
{
    protected static string $resource = RevenueSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
