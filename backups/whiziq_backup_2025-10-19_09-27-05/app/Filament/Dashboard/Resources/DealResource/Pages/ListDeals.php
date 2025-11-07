<?php

namespace App\Filament\Dashboard\Resources\DealResource\Pages;

use App\Filament\Dashboard\Resources\DealResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $userId = auth()->id();

        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->count()),

            'lead' => Tab::make('Lead')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', 'lead'))
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->where('stage', 'lead')->count()),

            'qualified' => Tab::make('Qualified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', 'qualified'))
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->where('stage', 'qualified')->count())
                ->badgeColor('primary'),

            'proposal' => Tab::make('Proposal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', 'proposal'))
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->where('stage', 'proposal')->count())
                ->badgeColor('warning'),

            'negotiation' => Tab::make('Negotiation')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', 'negotiation'))
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->where('stage', 'negotiation')->count())
                ->badgeColor('info'),

            'won' => Tab::make('Won')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', 'won'))
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->where('stage', 'won')->count())
                ->badgeColor('success'),

            'lost' => Tab::make('Lost')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', 'lost'))
                ->badge(fn () => $this->getModel()::where('user_id', $userId)->where('stage', 'lost')->count())
                ->badgeColor('danger'),
        ];
    }
}
