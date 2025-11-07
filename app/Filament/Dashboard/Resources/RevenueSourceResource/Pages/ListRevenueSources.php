<?php

namespace App\Filament\Dashboard\Resources\RevenueSourceResource\Pages;

use App\Filament\Dashboard\Resources\RevenueSourceResource;
use App\Filament\Dashboard\Resources\RevenueSourceResource\Widgets\RevenueSourceBreakdownWidget;
use App\Filament\Dashboard\Resources\RevenueSourceResource\Widgets\RevenueMonthlyTrendWidget;
use App\Services\RevenueSourceExportService;
use App\Services\RevenueSourceImportService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListRevenueSources extends ListRecords
{
    protected static string $resource = RevenueSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import Revenue Source')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV/Excel File')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required()
                        ->helperText('Upload a CSV or Excel file with revenue source data')
                        ->maxSize(5120), // 5MB
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(RevenueSourceImportService::class);

                        // Get file content
                        $filePath = storage_path('app/public/' . $data['file']);
                        
                        if (!file_exists($filePath)) {
                            Notification::make()
                                ->title('File Not Found')
                                ->danger()
                                ->body('The uploaded file could not be found.')
                                ->send();
                            return;
                        }

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
                        $message = "Successfully imported {$results['success']} revenue sources.";
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

            Action::make('download_template')
                ->label('Download CSV Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $service = app(RevenueSourceImportService::class);
                    $template = $service->getCsvTemplate();

                    return Response::streamDownload(function () use ($template) {
                        echo $template;
                    }, 'revenue_source_import_template.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Action::make('export')
                ->label('Export Revenue Sources')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Forms\Components\DatePicker::make('from_date')
                        ->label('From Date')
                        ->helperText('Export revenue sources from this date onwards'),

                    Forms\Components\DatePicker::make('until_date')
                        ->label('Until Date')
                        ->helperText('Export revenue sources up to this date'),

                    Forms\Components\Select::make('source')
                        ->label('Filter by Source')
                        ->options(function () {
                            return \App\Models\RevenueSource::where('user_id', auth()->id())
                                ->distinct()
                                ->pluck('source', 'source')
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->placeholder('All sources'),

                    Forms\Components\Select::make('format')
                        ->label('Export Format')
                        ->options([
                            'csv' => 'CSV',
                            'excel' => 'Excel (CSV)',
                        ])
                        ->default('csv')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $service = app(RevenueSourceExportService::class);
                    $format = $data['format'] ?? 'csv';
                    
                    $filters = [];
                    if (isset($data['from_date'])) {
                        $filters['from_date'] = $data['from_date'];
                    }
                    if (isset($data['until_date'])) {
                        $filters['until_date'] = $data['until_date'];
                    }
                    if (isset($data['source'])) {
                        $filters['source'] = $data['source'];
                    }

                    if ($format === 'excel') {
                        $content = $service->exportToExcel(auth()->id(), $filters);
                        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    } else {
                        $content = $service->exportToCsv(auth()->id(), $filters);
                        $contentType = 'text/csv';
                    }

                    $filename = $service->getExportFilename($format);

                    return Response::streamDownload(function () use ($content) {
                        echo $content;
                    }, $filename, [
                        'Content-Type' => $contentType,
                    ]);
                }),

            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RevenueSourceBreakdownWidget::class,
            RevenueMonthlyTrendWidget::class,
        ];
    }
}
