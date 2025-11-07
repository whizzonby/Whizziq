<?php

namespace App\Http\Controllers;

use App\Models\SocialMediaConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaOAuthController extends Controller
{
    /**
     * Handle OAuth callback from social media platforms
     */
    public function callback(Request $request, string $platform)
    {
        if ($request->has('error')) {
            return redirect()->route('filament.dashboard.resources.marketing-metrics.create')
                ->with('error', 'Connection cancelled or failed.');
        }

        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('filament.dashboard.resources.marketing-metrics.create')
                ->with('error', 'No authorization code received.');
        }

        try {
            $tokenData = $this->exchangeCodeForToken($code, $platform);

            if (!$tokenData) {
                throw new \Exception('Failed to exchange code for access token');
            }

            // Save or update connection
            $this->saveConnection($platform, $tokenData);

            return redirect()->route('filament.dashboard.resources.marketing-metrics.create')
                ->with('success', ucfirst($platform) . ' connected successfully! You can now fetch data.');
        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('filament.dashboard.resources.marketing-metrics.create')
                ->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }

    /**
     * Exchange authorization code for access token
     */
    protected function exchangeCodeForToken(string $code, string $platform): ?array
    {
        return match($platform) {
            'facebook' => $this->exchangeFacebookToken($code),
            'google' => $this->exchangeGoogleToken($code),
            'linkedin' => $this->exchangeLinkedInToken($code),
            default => null,
        };
    }

    /**
     * Exchange Facebook code for token
     */
    protected function exchangeFacebookToken(string $code): ?array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'redirect_uri' => route('marketing.oauth.callback', ['platform' => 'facebook']),
            'code' => $code,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 5184000, // 60 days default
            ];
        }

        return null;
    }

    /**
     * Exchange Google code for token
     */
    protected function exchangeGoogleToken(string $code): ?array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('marketing.oauth.callback', ['platform' => 'google']),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 3600,
            ];
        }

        return null;
    }

    /**
     * Exchange LinkedIn code for token
     */
    protected function exchangeLinkedInToken(string $code): ?array
    {
        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'client_id' => config('services.linkedin-openid.client_id'),
            'client_secret' => config('services.linkedin-openid.client_secret'),
            'redirect_uri' => route('marketing.oauth.callback', ['platform' => 'linkedin']),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 5184000,
            ];
        }

        return null;
    }

    /**
     * Save or update connection
     */
    protected function saveConnection(string $platform, array $tokenData): void
    {
        SocialMediaConnection::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'platform' => $platform,
            ],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'is_active' => true,
                'sync_status' => 'pending',
                'account_id' => $this->fetchAccountId($platform, $tokenData['access_token']),
                'account_name' => $this->fetchAccountName($platform, $tokenData['access_token']),
            ]
        );
    }

    /**
     * Fetch account ID from platform
     */
    protected function fetchAccountId(string $platform, string $accessToken): ?string
    {
        try {
            return match($platform) {
                'facebook' => $this->fetchFacebookAccountId($accessToken),
                'google' => 'google_ads_account', // Placeholder - needs customer ID input
                'linkedin' => $this->fetchLinkedInAccountId($accessToken),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Failed to fetch account ID', ['platform' => $platform, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch Facebook page/account ID
     */
    protected function fetchFacebookAccountId(string $accessToken): ?string
    {
        $response = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
            'access_token' => $accessToken,
        ]);

        if ($response->successful()) {
            $accounts = $response->json('data', []);
            return $accounts[0]['id'] ?? null; // Get first page
        }

        return null;
    }

    /**
     * Fetch LinkedIn account ID
     */
    protected function fetchLinkedInAccountId(string $accessToken): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'LinkedIn-Version' => '202310',
        ])->get('https://api.linkedin.com/rest/adAccounts');

        if ($response->successful()) {
            $accounts = $response->json('elements', []);
            if (!empty($accounts)) {
                // Extract account ID from URN
                $urn = $accounts[0]['id'] ?? null;
                return $urn ? str_replace('urn:li:sponsoredAccount:', '', $urn) : null;
            }
        }

        return null;
    }

    /**
     * Fetch account name from platform
     */
    protected function fetchAccountName(string $platform, string $accessToken): ?string
    {
        try {
            return match($platform) {
                'facebook' => $this->fetchFacebookAccountName($accessToken),
                'google' => 'Google Ads Account',
                'linkedin' => $this->fetchLinkedInAccountName($accessToken),
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Fetch Facebook page name
     */
    protected function fetchFacebookAccountName(string $accessToken): ?string
    {
        $response = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
            'access_token' => $accessToken,
        ]);

        if ($response->successful()) {
            $accounts = $response->json('data', []);
            return $accounts[0]['name'] ?? null;
        }

        return null;
    }

    /**
     * Fetch LinkedIn account name
     */
    protected function fetchLinkedInAccountName(string $accessToken): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'LinkedIn-Version' => '202310',
        ])->get('https://api.linkedin.com/rest/adAccounts');

        if ($response->successful()) {
            $accounts = $response->json('elements', []);
            return $accounts[0]['name'] ?? 'LinkedIn Ads Account';
        }

        return 'LinkedIn Ads Account';
    }
}
