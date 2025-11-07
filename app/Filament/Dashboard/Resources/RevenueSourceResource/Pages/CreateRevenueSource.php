<?php

namespace App\Filament\Dashboard\Resources\RevenueSourceResource\Pages;

use App\Filament\Dashboard\Resources\RevenueSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRevenueSource extends CreateRecord
{
    protected static string $resource = RevenueSourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return RevenueSourceResource::getUrl('index');
    }
}
