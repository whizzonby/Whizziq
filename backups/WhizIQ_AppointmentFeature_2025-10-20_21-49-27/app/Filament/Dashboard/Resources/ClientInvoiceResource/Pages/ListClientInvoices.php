<?php

namespace App\Filament\Dashboard\Resources\ClientInvoiceResource\Pages;

use App\Filament\Dashboard\Resources\ClientInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClientInvoices extends ListRecords
{
    protected static string $resource = ClientInvoiceResource::class;

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

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'draft')->count()),

            'sent' => Tab::make('Sent')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'sent')->count()),

            'overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'overdue'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'overdue')->count())
                ->badgeColor('danger'),

            'partial' => Tab::make('Partial')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'partial'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'partial')->count())
                ->badgeColor('warning'),

            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'paid')->count())
                ->badgeColor('success'),
        ];
    }
}
