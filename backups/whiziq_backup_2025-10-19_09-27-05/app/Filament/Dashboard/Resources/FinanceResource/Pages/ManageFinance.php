<?php

namespace App\Filament\Dashboard\Resources\FinanceResource\Pages;

use App\Filament\Dashboard\Resources\FinanceResource;
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
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/csv',
                    ])
                    ->maxSize(10240) // 10MB
                    ->required()
                    ->helperText('Upload an Excel (.xlsx, .xls) or CSV (.csv) file with your financial data.')
                    ->disk('local')
                    ->directory('finance-imports')
                    ->preserveFilenames()
                    ->columnSpanFull(),
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

            // TODO: Implement actual data fetching in service
            Notification::make()
                ->title('Import Started')
                ->body('Importing financial data from ' . ucfirst($platform) . '...')
                ->info()
                ->send();

            // Redirect to a processing page or show progress
        } catch (\Exception $e) {
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
            $data = $this->form->getState();

            if (!isset($data['file'])) {
                throw new \Exception('No file uploaded');
            }

            $filePath = storage_path('app/' . $data['file']);

            if (!file_exists($filePath)) {
                throw new \Exception('File not found');
            }

            // Get file extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Parse and import data
            $service = app(\App\Services\FinancialDataImportService::class);
            $result = $service->importFromFile($filePath, $extension, auth()->id());

            Notification::make()
                ->title('Import Successful!')
                ->body("Successfully imported {$result['imported']} records. {$result['skipped']} records were skipped.")
                ->success()
                ->send();

            // Close the modal
            $this->dispatch('close-modal', id: 'upload-excel-modal');

            // Reset form
            $this->form->fill();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
