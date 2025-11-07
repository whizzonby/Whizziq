<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\FinancialConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuickBooksImportService
{
    protected FinancialConnection $connection;

    public function __construct(FinancialConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Import transactions from QuickBooks
     */
    public function importTransactions(int $userId, int $days = 90): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            // Check if token is expired
            if ($this->isTokenExpired()) {
                $this->refreshAccessToken();
            }

            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');

            // Import Expenses
            $expensesResult = $this->importExpenses($userId, $startDate);
            $imported += $expensesResult['imported'];
            $skipped += $expensesResult['skipped'];
            $errors = array_merge($errors, $expensesResult['errors']);

            // Import Revenue (Invoices & Sales Receipts)
            $revenueResult = $this->importRevenue($userId, $startDate);
            $imported += $revenueResult['imported'];
            $skipped += $revenueResult['skipped'];
            $errors = array_merge($errors, $revenueResult['errors']);

            // Update last sync
            $this->connection->update([
                'last_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            Log::error('QuickBooks import failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        }
    }

    /**
     * Import expenses from QuickBooks
     */
    protected function importExpenses(int $userId, string $startDate): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $response = Http::withToken($this->connection->access_token)
                ->get($this->getApiUrl('/query'), [
                    'query' => "SELECT * FROM Purchase WHERE TxnDate >= '{$startDate}' ORDER BY TxnDate DESC",
                    'minorversion' => 65,
                ]);

            if (!$response->successful()) {
                throw new \Exception('QuickBooks API request failed: ' . $response->body());
            }

            $purchases = $response->json('QueryResponse.Purchase', []);

            foreach ($purchases as $purchase) {
                try {
                    // Check if already imported
                    $exists = Expense::where('user_id', $userId)
                        ->where('description', 'LIKE', "%QB-{$purchase['Id']}%")
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    Expense::create([
                        'user_id' => $userId,
                        'date' => Carbon::parse($purchase['TxnDate']),
                        'amount' => abs($purchase['TotalAmt'] ?? 0),
                        'category' => $this->mapQuickBooksCategory($purchase['AccountRef']['name'] ?? 'other'),
                        'description' => ($purchase['PrivateNote'] ?? 'QuickBooks Purchase') . " (QB-{$purchase['Id']})",
                        'is_tax_deductible' => $this->isBusinessExpense($purchase['AccountRef']['name'] ?? ''),
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Purchase ID {$purchase['Id']}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Expense import error: ' . $e->getMessage();
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Import revenue from QuickBooks (Invoices & Sales Receipts)
     */
    protected function importRevenue(int $userId, string $startDate): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            // Get Invoices
            $invoicesResponse = Http::withToken($this->connection->access_token)
                ->get($this->getApiUrl('/query'), [
                    'query' => "SELECT * FROM Invoice WHERE TxnDate >= '{$startDate}' ORDER BY TxnDate DESC",
                    'minorversion' => 65,
                ]);

            if ($invoicesResponse->successful()) {
                $invoices = $invoicesResponse->json('QueryResponse.Invoice', []);

                foreach ($invoices as $invoice) {
                    try {
                        $exists = RevenueSource::where('user_id', $userId)
                            ->where('description', 'LIKE', "%QB-{$invoice['Id']}%")
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        RevenueSource::create([
                            'user_id' => $userId,
                            'date' => Carbon::parse($invoice['TxnDate']),
                            'amount' => $invoice['TotalAmt'] ?? 0,
                            'source' => 'online_sales',
                            'description' => "Invoice #{$invoice['DocNumber']} (QB-{$invoice['Id']})",
                        ]);

                        $imported++;

                    } catch (\Exception $e) {
                        $skipped++;
                        $errors[] = "Invoice ID {$invoice['Id']}: " . $e->getMessage();
                    }
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Revenue import error: ' . $e->getMessage();
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Check if access token is expired
     */
    protected function isTokenExpired(): bool
    {
        if (!$this->connection->token_expires_at) {
            return true;
        }

        return Carbon::parse($this->connection->token_expires_at)->isPast();
    }

    /**
     * Refresh QuickBooks access token
     */
    protected function refreshAccessToken(): void
    {
        $response = Http::asForm()->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->connection->refresh_token,
            'client_id' => config('services.quickbooks.client_id'),
            'client_secret' => config('services.quickbooks.client_secret'),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            $this->connection->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $this->connection->refresh_token,
                'token_expires_at' => now()->addSeconds($data['expires_in']),
            ]);
        } else {
            throw new \Exception('Failed to refresh QuickBooks token');
        }
    }

    /**
     * Get QuickBooks API URL
     */
    protected function getApiUrl(string $endpoint): string
    {
        $environment = config('services.quickbooks.environment', 'sandbox');
        $baseUrl = $environment === 'sandbox'
            ? 'https://sandbox-quickbooks.api.intuit.com'
            : 'https://quickbooks.api.intuit.com';

        $realmId = $this->connection->metadata['realmId'] ?? $this->connection->account_id ?? '';

        return "{$baseUrl}/v3/company/{$realmId}{$endpoint}";
    }

    /**
     * Map QuickBooks category to app category
     */
    protected function mapQuickBooksCategory(string $qbCategory): string
    {
        $categoryMap = [
            'Advertising' => 'marketing',
            'Office Supplies' => 'supplies',
            'Rent' => 'rent',
            'Utilities' => 'utilities',
            'Insurance' => 'insurance',
            'Payroll' => 'salaries',
            'Software' => 'software',
            'Travel' => 'travel',
            'Meals & Entertainment' => 'meals_entertainment',
        ];

        return $categoryMap[$qbCategory] ?? 'other';
    }

    /**
     * Determine if expense is tax deductible
     */
    protected function isBusinessExpense(string $accountName): bool
    {
        $businessExpenses = [
            'Advertising', 'Office Supplies', 'Rent', 'Utilities',
            'Insurance', 'Software', 'Travel', 'Payroll'
        ];

        foreach ($businessExpenses as $expense) {
            if (str_contains($accountName, $expense)) {
                return true;
            }
        }

        return false;
    }
}
