<?php

namespace App\Filament\Dashboard\Resources\ContactResource\Pages;

use App\Filament\Dashboard\Resources\ContactResource;
use App\Services\ContactExportService;
use App\Services\ContactImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import Contacts')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'text/plain'])
                        ->required()
                        ->helperText('Upload a CSV file with contact data')
                        ->maxSize(5120), // 5MB
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(ContactImportService::class);

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
                        $message = "Successfully imported {$results['success']} contacts.";
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
                    $service = app(ContactImportService::class);
                    $template = $service->getCsvTemplate();

                    return Response::streamDownload(function () use ($template) {
                        echo $template;
                    }, 'contact_import_template.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Action::make('export')
                ->label('Export Contacts')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('type')
                        ->label('Filter by Type')
                        ->options([
                            'client' => 'Clients',
                            'lead' => 'Leads',
                            'partner' => 'Partners',
                            'investor' => 'Investors',
                            'vendor' => 'Vendors',
                        ])
                        ->placeholder('All types'),

                    Forms\Components\Select::make('status')
                        ->label('Filter by Status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'archived' => 'Archived',
                        ])
                        ->placeholder('All statuses'),
                ])
                ->action(function (array $data) {
                    $service = app(ContactExportService::class);
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
