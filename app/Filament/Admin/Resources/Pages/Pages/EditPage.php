<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public static bool $formActionsAreSticky = true;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('View Page'))
                ->color('success')
                ->url(
                    fn (Page $resource) => $resource->url,
                    true
                )
                ->icon('heroicon-o-eye'),
            ActionGroup::make([
                DeleteAction::make(),
            ]),
        ];
    }

    protected function getFormActions(): array
    {
        return array_merge(parent::getFormActions(), [
            Action::make('view')
                ->label(__('View Page'))
                ->color('success')
                ->url(
                    fn (Page $resource) => $resource->url,
                    true
                )
                ->icon('heroicon-o-eye'),
        ]);
    }
}
