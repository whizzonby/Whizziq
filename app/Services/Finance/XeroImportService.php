<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\FinancialConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroImportService
{
    protected FinancialConnection $connection;

    public function __construct(FinancialConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Import transactions from Xero
     */
    public function importTransactions(int $userId, int $days = 90): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            if ($this->isTokenExpired()) {
                $this->refreshAccessToken();
            }

            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');

            // Import Bank Transactions
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->connection->access_token,
                'Xero-tenant-id' => $this->connection->metadata['tenantId'] ?? '',
                'Accept' => 'application/json',
            ])->get('https://api.xero.com/api.xro/2.0/BankTransactions', [
                'where' => 'Date >= DateTime(' . $startDate . ')',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Xero API request failed: ' . $response->body());
            }

            $transactions = $response->json('BankTransactions', []);

            foreach ($transactions as $transaction) {
                try {
                    $type = $transaction['Type'] ?? '';
                    $transactionId = $transaction['BankTransactionID'] ?? '';

                    if ($type === 'SPEND' || $type === 'SPEND-OVERPAYMENT') {
                        // Import as expense
                        $exists = Expense::where('user_id', $userId)
                            ->where('description', 'LIKE', "%XERO-{$transactionId}%")
                            ->exists();

                        if (!$exists) {
                            Expense::create([
                                'user_id' => $userId,
                                'date' => Carbon::parse($transaction['Date']),
                                'amount' => abs($transaction['Total'] ?? 0),
                                'category' => $this->mapXeroCategory($transaction['LineItems'][0]['AccountCode'] ?? 'other'),
                                'description' => ($transaction['Reference'] ?? 'Xero Transaction') . " (XERO-{$transactionId})",
                                'is_tax_deductible' => true,
                            ]);
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($type === 'RECEIVE' || $type === 'RECEIVE-OVERPAYMENT') {
                        // Import as revenue
                        $exists = RevenueSource::where('user_id', $userId)
                            ->where('description', 'LIKE', "%XERO-{$transactionId}%")
                            ->exists();

                        if (!$exists) {
                            RevenueSource::create([
                                'user_id' => $userId,
                                'date' => Carbon::parse($transaction['Date']),
                                'amount' => $transaction['Total'] ?? 0,
                                'source' => 'online_sales',
                                'description' => ($transaction['Reference'] ?? 'Xero Transaction') . " (XERO-{$transactionId})",
                            ]);
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    }

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Transaction ID {$transactionId}: " . $e->getMessage();
                }
            }

            $this->connection->update(['last_synced_at' => now()]);

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            Log::error('Xero import failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        }
    }

    protected function isTokenExpired(): bool
    {
        if (!$this->connection->token_expires_at) {
            return true;
        }

        return Carbon::parse($this->connection->token_expires_at)->isPast();
    }

    protected function refreshAccessToken(): void
    {
        $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->connection->refresh_token,
            'client_id' => config('services.xero.client_id'),
            'client_secret' => config('services.xero.client_secret'),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            $this->connection->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $this->connection->refresh_token,
                'token_expires_at' => now()->addSeconds($data['expires_in']),
            ]);
        } else {
            throw new \Exception('Failed to refresh Xero token');
        }
    }

    protected function mapXeroCategory(string $accountCode): string
    {
        // Map Xero account codes to app categories
        $categoryMap = [
            '400' => 'marketing',
            '404' => 'supplies',
            '420' => 'rent',
            '445' => 'utilities',
            '485' => 'insurance',
            '477' => 'salaries',
        ];

        return $categoryMap[$accountCode] ?? 'other';
    }
}
