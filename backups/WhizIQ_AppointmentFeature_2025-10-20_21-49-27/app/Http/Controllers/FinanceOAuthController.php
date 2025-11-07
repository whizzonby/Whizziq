<?php

namespace App\Http\Controllers;

use App\Models\FinancialConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FinanceOAuthController extends Controller
{
    /**
     * Handle OAuth callback from financial platforms
     */
    public function callback(Request $request, string $platform)
    {
        if ($request->has('error')) {
            return redirect()->route('filament.dashboard.resources.finances.index')
                ->with('error', 'Connection cancelled or failed.');
        }

        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('filament.dashboard.resources.finances.index')
                ->with('error', 'No authorization code received.');
        }

        try {
            $tokenData = $this->exchangeCodeForToken($code, $platform);

            if (!$tokenData) {
                throw new \Exception('Failed to exchange code for access token');
            }

            // Save or update connection
            $this->saveConnection($platform, $tokenData);

            return redirect()->route('filament.dashboard.resources.finances.index')
                ->with('success', ucfirst($platform) . ' connected successfully! You can now import data.');
        } catch (\Exception $e) {
            Log::error('Finance OAuth callback error', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('filament.dashboard.resources.finances.index')
                ->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }

    /**
     * Exchange authorization code for access token
     */
    protected function exchangeCodeForToken(string $code, string $platform): ?array
    {
        return match($platform) {
            'quickbooks' => $this->exchangeQuickBooksToken($code),
            'xero' => $this->exchangeXeroToken($code),
            'stripe' => $this->exchangeStripeToken($code),
            default => null,
        };
    }

    /**
     * Exchange QuickBooks code for token
     */
    protected function exchangeQuickBooksToken(string $code): ?array
    {
        $response = Http::asForm()->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('finance.oauth.callback', ['platform' => 'quickbooks']),
        ])->withBasicAuth(
            config('services.quickbooks.client_id'),
            config('services.quickbooks.client_secret')
        );

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 3600,
                'realm_id' => $data['realmId'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Exchange Xero code for token
     */
    protected function exchangeXeroToken(string $code): ?array
    {
        $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('finance.oauth.callback', ['platform' => 'xero']),
            'client_id' => config('services.xero.client_id'),
            'client_secret' => config('services.xero.client_secret'),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 1800,
            ];
        }

        return null;
    }

    /**
     * Exchange Stripe code for token
     */
    protected function exchangeStripeToken(string $code): ?array
    {
        $response = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_secret' => config('services.stripe.secret'),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => null, // Stripe tokens don't expire
                'stripe_user_id' => $data['stripe_user_id'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Save or update connection
     */
    protected function saveConnection(string $platform, array $tokenData): void
    {
        FinancialConnection::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'platform' => $platform,
            ],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'is_active' => true,
                'sync_status' => 'pending',
                'account_id' => $tokenData['realm_id'] ?? $tokenData['stripe_user_id'] ?? null,
                'account_name' => $this->fetchAccountName($platform, $tokenData['access_token']),
            ]
        );
    }

    /**
     * Fetch account name from platform
     */
    protected function fetchAccountName(string $platform, string $accessToken): ?string
    {
        try {
            return match($platform) {
                'quickbooks' => 'QuickBooks Account',
                'xero' => $this->fetchXeroAccountName($accessToken),
                'stripe' => 'Stripe Account',
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Fetch Xero account name
     */
    protected function fetchXeroAccountName(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('https://api.xero.com/connections');

        if ($response->successful()) {
            $connections = $response->json();
            return $connections[0]['tenantName'] ?? 'Xero Account';
        }

        return 'Xero Account';
    }

    /**
     * Download CSV template for manual data entry
     */
    public function downloadTemplate()
    {
        $csvContent = "Date,Description,Amount,Type,Category\n";
        $csvContent .= "2025-01-15,Office Supplies Purchase,250.00,Expense,Office Supplies\n";
        $csvContent .= "2025-01-16,Client Payment - Project A,5000.00,Revenue,Consulting\n";
        $csvContent .= "2025-01-17,Marketing Campaign,800.00,Expense,Marketing\n";
        $csvContent .= "2025-01-18,Product Sale - Widget,1500.00,Revenue,Sales\n";
        $csvContent .= "2025-01-19,Monthly Rent,2000.00,Expense,Rent\n";

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="financial_data_template.csv"');
    }
}
