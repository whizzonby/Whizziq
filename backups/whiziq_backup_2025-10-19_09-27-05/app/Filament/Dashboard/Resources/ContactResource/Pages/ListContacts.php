<?php

namespace App\Filament\Dashboard\Resources\ContactResource\Pages;

use App\Filament\Dashboard\Resources\ContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->count()),

            'clients' => Tab::make('Clients')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'client'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('type', 'client')->count())
                ->badgeColor('success'),

            'leads' => Tab::make('Leads')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'lead'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('type', 'lead')->count())
                ->badgeColor('primary'),

            'vip' => Tab::make('VIP')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('priority', 'vip'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('priority', 'vip')->count())
                ->badgeColor('danger'),

            'needs_follow_up' => Tab::make('Needs Follow-Up')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsFollowUp())
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->needsFollowUp()->count())
                ->badgeColor('warning'),
        ];
    }
}
