<?php

namespace App\Filament\Dashboard\Resources\AppointmentResource\Pages;

use App\Filament\Dashboard\Resources\AppointmentResource;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use App\Services\RecurringAppointmentService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    public function mount(): void
    {
        if (!AppointmentResource::canCreate()) {
            Notification::make()
                ->title('Subscription Required')
                ->body('Please subscribe to a plan to create appointments. Choose a plan that fits your needs!')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(SubscriptionResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['booked_via'] = 'admin';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Generate recurring instances if appointment is recurring
        if ($this->record->is_recurring && $this->record->isRecurringParent()) {
            $service = new RecurringAppointmentService();
            $instances = $service->createRecurringInstances($this->record);

            Notification::make()
                ->title('Recurring Appointment Created')
                ->success()
                ->body(count($instances) . ' recurring instances have been created.')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
