<?php

namespace App\Filament\Dashboard\Resources\TaxDocumentResource\Pages;

use App\Filament\Dashboard\Resources\TaxDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTaxDocuments extends ListRecords
{
    protected static string $resource = TaxDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Upload Document')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Documents'),

            'w2' => Tab::make('W-2 Forms')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'w2'))
                ->badge(fn() => \App\Models\TaxDocument::where('user_id', auth()->id())
                    ->where('document_type', 'w2')
                    ->count()),

            '1099' => Tab::make('1099 Forms')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('document_type', ['1099_nec', '1099_misc', '1099_int', '1099_div']))
                ->badge(fn() => \App\Models\TaxDocument::where('user_id', auth()->id())
                    ->whereIn('document_type', ['1099_nec', '1099_misc', '1099_int', '1099_div'])
                    ->count()),

            'receipts' => Tab::make('Receipts')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('document_type', ['receipt', 'invoice']))
                ->badge(fn() => \App\Models\TaxDocument::where('user_id', auth()->id())
                    ->whereIn('document_type', ['receipt', 'invoice'])
                    ->count()),

            'pending' => Tab::make('Pending Verification')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('verification_status', 'pending'))
                ->badge(fn() => \App\Models\TaxDocument::where('user_id', auth()->id())
                    ->where('verification_status', 'pending')
                    ->count())
                ->badgeColor('warning'),
        ];
    }
}
