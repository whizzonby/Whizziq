<?php

namespace App\Filament\Dashboard\Resources\DealResource\Pages;

use App\Filament\Dashboard\Resources\DealResource;
use App\Services\DealExportService;
use App\Services\DealImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Download CSV Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $service = app(DealImportService::class);
                    $template = $service->getCsvTemplate();

                    return Response::streamDownload(function () use ($template) {
                        echo $template;
                    }, 'deal_import_template.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Action::make('import')
                ->label('Import Deals')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain'])
                        ->required()
                        ->helperText('Upload a CSV file with deal data')
                        ->maxSize(5120), // 5MB

                ])
                ->action(function (array $data) {
                    try {
                        $service = app(DealImportService::class);

                        // Get file content
                        $filePath = storage_path('app/public/' . $data['file']);
                        $csvContent = file_get_contents($filePath);

                        // Validate structure
                        $validation = $service->validateCsvStructure($csvContent);
                        if (!$validation['valid']) {
                            Notification::make()
                                ->title('Invalid CSV')
                                ->danger()
                                ->body($validation['message'])
                                ->send();
                            return;
                        }

                        // Import
                        $results = $service->importFromCsv($csvContent, auth()->id());

                        // Show results
                        $message = "Successfully imported {$results['success']} deals.";
                        if ($results['failed'] > 0) {
                            $message .= " {$results['failed']} failed.";
                        }

                        Notification::make()
                            ->title('Import Complete')
                            ->success()
                            ->body($message)
                            ->send();

                        if (!empty($results['errors'])) {
                            foreach (array_slice($results['errors'], 0, 5) as $error) {
                                Notification::make()
                                    ->title('Import Error')
                                    ->warning()
                                    ->body($error)
                                    ->send();
                            }
                        }

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('export')
                ->label('Export Deals')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('stage')
                        ->label('Filter by Stage')
                        ->options([
                            'lead' => 'Lead',
                            'qualified' => 'Qualified',
                            'proposal' => 'Proposal',
                            'negotiation' => 'Negotiation',
                            'won' => 'Won',
                            'lost' => 'Lost',
                        ])
                        ->placeholder('All stages'),

                    Forms\Components\Select::make('priority')
                        ->label('Filter by Priority')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'vip' => 'VIP',
                        ])
                        ->placeholder('All priorities'),

                    Forms\Components\Select::make('status')
                        ->label('Filter by Status')
                        ->options([
                            'open' => 'Open Deals',
                            'closed' => 'Closed Deals',
                            'won' => 'Won Only',
                            'lost' => 'Lost Only',
                        ])
                        ->placeholder('All deals'),
                ])
                ->action(function (array $data) {
                    $service = app(DealExportService::class);
                    $csv = $service->exportToCsv(auth()->id(), $data);
                    $filename = $service->getExportFilename();

                    return Response::streamDownload(function () use ($csv) {
                        echo $csv;
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

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
