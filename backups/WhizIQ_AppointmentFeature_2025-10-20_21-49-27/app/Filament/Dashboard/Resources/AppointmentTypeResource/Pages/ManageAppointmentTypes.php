<?php

namespace App\Filament\Dashboard\Resources\AppointmentTypeResource\Pages;

use App\Filament\Dashboard\Resources\AppointmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAppointmentTypes extends ManageRecords
{
    protected static string $resource = AppointmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    return $data;
                }),
        ];
    }
}
