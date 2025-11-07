<?php

namespace App\Filament\Dashboard\Resources\FinanceResource\Pages;

use App\Filament\Dashboard\Resources\FinanceResource;
use App\Filament\Dashboard\Resources\FinanceResource\Widgets\FinanceImportHistoryWidget;
use App\Filament\Dashboard\Resources\FinanceResource\Widgets\FinanceMetricsSummaryWidget;
use App\Models\FinancialConnection;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageFinance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FinanceResource::class;

    protected string $view = 'filament.dashboard.resources.finance-resource.pages.manage-finance';

    protected static ?string $title = 'Finance - Import Your Financial Data';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file')
                    ->label('Financial Data File')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/csv',
                    ])
                    ->maxSize(10240) // 10MB
                    ->required()
                    ->helperText('Upload an Excel (.xlsx) or CSV (.csv) file with your financial data.')
                    ->columnSpanFull()
                    ->storeFiles(false), // Don't store the file permanently, just use temp file
            ])
            ->statePath('data');
    }

    public function getConnections()
    {
        return FinancialConnection::where('user_id', auth()->id())
            ->get()
            ->keyBy('platform');
    }

    // QuickBooks OAuth
    public function connectQuickBooks()
    {
        $clientId = config('services.quickbooks.client_id');
        $redirectUri = route('finance.oauth.callback', ['platform' => 'quickbooks']);
        $scopes = 'com.intuit.quickbooks.accounting';

        if (!$clientId) {
            $this->showComingSoon('QuickBooks');
            return;
        }

        $url = "https://appcenter.intuit.com/connect/oauth2?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    // Xero OAuth
    public function connectXero()
    {
        $clientId = config('services.xero.client_id');
        $redirectUri = route('finance.oauth.callback', ['platform' => 'xero']);
        $scopes = 'offline_access accounting.transactions accounting.reports.read';

        if (!$clientId) {
            $this->showComingSoon('Xero');
            return;
        }

        $url = "https://login.xero.com/identity/connect/authorize?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    // Stripe OAuth
    public function connectStripe()
    {
        $clientId = config('services.stripe.connect_client_id');
        $redirectUri = route('finance.oauth.callback', ['platform' => 'stripe']);

        if (!$clientId) {
            $this->showComingSoon('Stripe');
            return;
        }

        $url = "https://connect.stripe.com/oauth/authorize?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'read_only',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    // Enterprise platforms
    public function showEnterprise(string $platform)
    {
        Notification::make()
            ->title('Enterprise Integration')
            ->body("{$platform} integration is available for enterprise customers. Contact our sales team to get started.")
            ->info()
            ->persistent()
            ->actions([
                Action::make('contact')
                    ->label('Contact Sales')
                    ->url('mailto:sales@whiziq.com')
                    ->openUrlInNewTab(),
            ])
            ->send();
    }

    // Coming Soon
    public function showComingSoon(string $platform = 'This platform')
    {
        Notification::make()
            ->title('Coming Soon')
            ->body("{$platform} integration is coming soon! We're working hard to bring you this feature.")
            ->info()
            ->send();
    }

    // Disconnect platform
    public function disconnectPlatform(string $platform)
    {
        try {
            $connection = FinancialConnection::where('user_id', auth()->id())
                ->where('platform', $platform)
                ->first();

            if ($connection) {
                $platformName = $connection->platform_name ?? ucfirst($platform);
                $connection->delete();

                Notification::make()
                    ->title('Disconnected Successfully')
                    ->body("{$platformName} has been disconnected from your account.")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Disconnect Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Fetch and import data
    public function importFromPlatform(string $platform)
    {
        try {
            $connection = FinancialConnection::where('user_id', auth()->id())
                ->where('platform', $platform)
                ->firstOrFail();

            // Update sync status
            $connection->update(['sync_status' => 'syncing']);

            // Get platform-specific import service
            $importService = match($platform) {
                'quickbooks' => new \App\Services\Finance\QuickBooksImportService($connection),
                'xero' => new \App\Services\Finance\XeroImportService($connection),
                'stripe' => new \App\Services\Finance\StripeImportService($connection),
                default => throw new \Exception("Import service not available for {$platform}"),
            };

            // Import transactions
            $result = $importService->importTransactions(auth()->id(), 90);

            if ($result['success']) {
                $connection->update(['sync_status' => 'completed']);

                Notification::make()
                    ->title('Import Successful!')
                    ->body("Imported {$result['imported']} records from " . ucfirst($platform) . ". " .
                           ($result['skipped'] > 0 ? "{$result['skipped']} records were skipped." : ""))
                    ->success()
                    ->send();
            } else {
                $connection->update(['sync_status' => 'failed']);

                Notification::make()
                    ->title('Import Completed with Errors')
                    ->body("Imported {$result['imported']} records, but encountered some errors.")
                    ->warning()
                    ->send();
            }

        } catch (\Exception $e) {
            if (isset($connection)) {
                $connection->update(['sync_status' => 'failed']);
            }

            Notification::make()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Upload and process Excel/CSV file
    public function uploadFinancialFile()
    {
        try {
            // Increase execution time for large files
            set_time_limit(300); // 5 minutes
            ini_set('memory_limit', '512M'); // Increase memory limit

            // Validate the form
            $data = $this->form->getState();

            if (!isset($data['file']) || empty($data['file'])) {
                throw new \Exception('No file uploaded');
            }

            // Get the TemporaryUploadedFile object
            $uploadedFile = $data['file'];

            // Since storeFiles(false), this will be a TemporaryUploadedFile object
            if (!is_object($uploadedFile) || !method_exists($uploadedFile, 'getRealPath')) {
                throw new \Exception('Invalid file upload. Please try again.');
            }

            // Get the real file path from the temporary uploaded file
            $filePath = $uploadedFile->getRealPath();

            if (!$filePath || !file_exists($filePath)) {
                throw new \Exception('Uploaded file not found. Please try uploading again.');
            }

            // Get file extension from the original filename
            $extension = strtolower($uploadedFile->getClientOriginalExtension());

            \Log::info('Starting financial data import', [
                'file' => $uploadedFile->getClientOriginalName(),
                'extension' => $extension,
                'size' => filesize($filePath),
                'user_id' => auth()->id(),
            ]);

            // Parse and import data
            $service = app(\App\Services\FinancialDataImportService::class);
            $result = $service->importFromFile($filePath, $extension, auth()->id());

            \Log::info('Financial data import completed', [
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
            ]);

            // Show appropriate notification based on results
            if ($result['imported'] > 0) {
                $message = "Successfully imported {$result['imported']} records.";
                if ($result['skipped'] > 0) {
                    $message .= " {$result['skipped']} records were skipped.";
                }

                Notification::make()
                    ->title('Import Successful!')
                    ->body($message)
                    ->success()
                    ->duration(5000)
                    ->send();
            } else {
                $message = "No records were imported.";
                if ($result['skipped'] > 0) {
                    $message .= " {$result['skipped']} records were skipped due to validation errors.";
                }

                Notification::make()
                    ->title('Import Completed')
                    ->body($message)
                    ->warning()
                    ->duration(5000)
                    ->send();
            }

            // Close the modal
            $this->dispatch('close-modal', id: 'upload-excel-modal');

            // Reset form
            $this->form->fill();

        } catch (\Exception $e) {
            \Log::error('Financial data import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Header widgets - Show finance metrics summary
     */
    protected function getHeaderWidgets(): array
    {
        return [
            FinanceMetricsSummaryWidget::class,
        ];
    }

    /**
     * Footer widgets - Show import history
     */
    protected function getFooterWidgets(): array
    {
        return [
            FinanceImportHistoryWidget::class,
        ];
    }
}
