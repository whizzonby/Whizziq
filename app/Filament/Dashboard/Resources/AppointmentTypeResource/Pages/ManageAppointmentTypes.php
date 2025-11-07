<?php

namespace App\Filament\Dashboard\Resources\AppointmentTypeResource\Pages;

use App\Filament\Dashboard\Resources\AppointmentTypeResource;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageAppointmentTypes extends ManageRecords
{
    protected static string $resource = AppointmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->before(function () {
                    if (!AppointmentTypeResource::canCreate()) {
                        Notification::make()
                            ->title('Subscription Required')
                            ->body('Please subscribe to a plan to create appointment types. Choose a plan that fits your needs!')
                            ->warning()
                            ->persistent()
                            ->send();

                        $this->redirect(SubscriptionResource::getUrl('index'));
                    }
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    return $data;
                }),
        ];
    }
}
