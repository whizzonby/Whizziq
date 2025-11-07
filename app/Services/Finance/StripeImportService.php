<?php

namespace App\Services\Finance;

use App\Models\RevenueSource;
use App\Models\FinancialConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeImportService
{
    protected FinancialConnection $connection;

    public function __construct(FinancialConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Import transactions from Stripe
     */
    public function importTransactions(int $userId, int $days = 90): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $startTimestamp = Carbon::now()->subDays($days)->timestamp;

            // Import Charges (Revenue)
            $response = Http::withToken($this->connection->access_token)
                ->get('https://api.stripe.com/v1/charges', [
                    'limit' => 100,
                    'created[gte]' => $startTimestamp,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Stripe API request failed: ' . $response->body());
            }

            $charges = $response->json('data', []);

            foreach ($charges as $charge) {
                try {
                    // Only import successful charges
                    if ($charge['status'] !== 'succeeded') {
                        $skipped++;
                        continue;
                    }

                    $chargeId = $charge['id'] ?? '';

                    // Check if already imported
                    $exists = RevenueSource::where('user_id', $userId)
                        ->where('description', 'LIKE', "%STRIPE-{$chargeId}%")
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    RevenueSource::create([
                        'user_id' => $userId,
                        'date' => Carbon::createFromTimestamp($charge['created']),
                        'amount' => $charge['amount'] / 100, // Stripe amounts are in cents
                        'source' => $this->determineRevenueSource($charge),
                        'description' => $this->buildDescription($charge) . " (STRIPE-{$chargeId})",
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Charge ID {$chargeId}: " . $e->getMessage();
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
            Log::error('Stripe import failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        }
    }

    protected function determineRevenueSource(array $charge): string
    {
        // Check if it's a subscription charge
        if (isset($charge['invoice'])) {
            return 'subscriptions';
        }

        // Check metadata for source type
        if (isset($charge['metadata']['source_type'])) {
            return $charge['metadata']['source_type'];
        }

        return 'online_sales';
    }

    protected function buildDescription(array $charge): string
    {
        $parts = [];

        if (isset($charge['description'])) {
            $parts[] = $charge['description'];
        }

        if (isset($charge['billing_details']['name'])) {
            $parts[] = 'from ' . $charge['billing_details']['name'];
        }

        if (isset($charge['payment_method_details']['type'])) {
            $parts[] = '(' . ucfirst($charge['payment_method_details']['type']) . ')';
        }

        return !empty($parts) ? implode(' ', $parts) : 'Stripe Payment';
    }
}
