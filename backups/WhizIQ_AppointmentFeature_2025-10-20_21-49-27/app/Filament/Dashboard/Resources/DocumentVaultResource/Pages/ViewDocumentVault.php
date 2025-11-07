<?php

namespace App\Filament\Dashboard\Resources\DocumentVaultResource\Pages;

use App\Filament\Dashboard\Resources\DocumentVaultResource;
use App\Services\DocumentAnalysisService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentVault extends ViewRecord
{
    protected static string $resource = DocumentVaultResource::class;

    protected string $view = 'filament.dashboard.resources.document-vault-resource.view-document';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analyze')
                ->label('AI Analyze')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => config('services.openai.key'))
                ->requiresConfirmation()
                ->modalHeading('AI Document Analysis')
                ->modalDescription('This will analyze your document using AI and generate a comprehensive summary, key points, and detailed insights. This may take a few moments.')
                ->modalSubmitActionLabel('Analyze Now')
                ->action(function () {
                    try {
                        $service = app(DocumentAnalysisService::class);
                        $result = $service->analyzeDocument($this->getRecord());

                        if ($result['success']) {
                            Notification::make()
                                ->title('Analysis Complete!')
                                ->body('Your document has been analyzed successfully.')
                                ->success()
                                ->send();

                            // Refresh the page to show analysis
                            redirect()->to($this->getUrl());
                        } else {
                            Notification::make()
                                ->title('Analysis Failed')
                                ->body($result['error'] ?? 'Could not analyze document')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred during analysis: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return $this->getRecord()->download();
                }),

            Actions\Action::make('favorite')
                ->label(fn () => $this->getRecord()->is_favorite ? 'Remove from Favorites' : 'Add to Favorites')
                ->icon(fn () => $this->getRecord()->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                ->color('warning')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['is_favorite' => !$record->is_favorite]);

                    Notification::make()
                        ->title($record->is_favorite ? 'Added to Favorites' : 'Removed from Favorites')
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->color('gray'),

            Actions\DeleteAction::make()
                ->successRedirectUrl(DocumentVaultResource::getUrl('index')),
        ];
    }
}
