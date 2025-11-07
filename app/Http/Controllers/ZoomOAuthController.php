<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Services\Zoom\ZoomOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZoomOAuthController extends Controller
{
    protected ZoomOAuthService $zoomOAuthService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->zoomOAuthService = new ZoomOAuthService();
    }

    /**
     * Redirect to Zoom OAuth authorization
     */
    public function redirectToZoom(Request $request)
    {
        if (!$this->zoomOAuthService->isConfigured()) {
            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Zoom is not configured. Please add OAuth credentials to your .env file.');
        }

        // Generate state token for CSRF protection
        $state = Str::random(40);
        session(['zoom_oauth_state' => $state]);

        $authUrl = $this->zoomOAuthService->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Zoom
     */
    public function handleZoomCallback(Request $request)
    {
        // Check for errors
        if ($request->has('error')) {
            Log::warning('Zoom OAuth error', [
                'error' => $request->input('error'),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Zoom connection was cancelled or failed.');
        }

        // Verify state token (CSRF protection)
        $state = $request->input('state');
        $sessionState = session('zoom_oauth_state');

        if (!$state || $state !== $sessionState) {
            Log::error('Zoom OAuth state mismatch', [
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        // Clear state from session
        session()->forget('zoom_oauth_state');

        // Get authorization code
        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'No authorization code received from Zoom.');
        }

        try {
            // Exchange code for tokens
            $tokenData = $this->zoomOAuthService->exchangeCode($code);

            if (!$tokenData) {
                throw new \Exception('Failed to exchange authorization code for tokens');
            }

            // Create temporary connection to fetch user info
            $tempConnection = new CalendarConnection([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);

            // Get user's Zoom account info
            $userInfo = $this->zoomOAuthService->getUser($tempConnection);

            // Check if user already has a Zoom connection
            $existingConnection = CalendarConnection::where('user_id', auth()->id())
                ->where('provider', 'zoom')
                ->exists();

            // Save or update the Zoom connection
            $connection = CalendarConnection::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'provider' => 'zoom',
                ],
                [
                    'provider_user_id' => $userInfo['id'] ?? null,
                    'provider_email' => $userInfo['email'] ?? null,
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                    'is_primary' => true, // Zoom connections are always primary (only one per user)
                    'sync_enabled' => true,
                    'scopes' => isset($tokenData['scope']) ? explode(' ', $tokenData['scope']) : null,
                ]
            );

            Log::info('Zoom connected successfully', [
                'user_id' => auth()->id(),
                'connection_id' => $connection->id,
                'email' => $userInfo['email'] ?? 'unknown',
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('success', 'Zoom connected successfully! Your appointments will now automatically create Zoom meetings.');

        } catch (\Exception $e) {
            Log::error('Zoom OAuth callback error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Failed to connect Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Zoom
     */
    public function disconnectZoom(Request $request)
    {
        try {
            $connection = CalendarConnection::where('user_id', auth()->id())
                ->where('provider', 'zoom')
                ->first();

            if ($connection) {
                // Optionally revoke the token with Zoom
                try {
                    $this->zoomOAuthService->revokeToken($connection);
                } catch (\Exception $e) {
                    Log::warning('Failed to revoke Zoom token during disconnect', [
                        'connection_id' => $connection->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $connection->delete();

                Log::info('Zoom disconnected', [
                    'user_id' => auth()->id(),
                    'connection_id' => $connection->id,
                ]);

                return redirect()->route('filament.dashboard.pages.booking-settings-page')
                    ->with('success', 'Zoom disconnected successfully.');
            }

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Zoom connection not found.');

        } catch (\Exception $e) {
            Log::error('Zoom disconnect error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Failed to disconnect Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Test Zoom connection
     */
    public function testConnection(Request $request)
    {
        try {
            $connection = CalendarConnection::where('user_id', auth()->id())
                ->where('provider', 'zoom')
                ->firstOrFail();

            // Try to fetch user profile to test the connection
            $userInfo = $this->zoomOAuthService->getUser($connection);

            if (!$userInfo) {
                throw new \Exception('Failed to fetch Zoom user profile. Token may be invalid.');
            }

            Log::info('Zoom connection test successful', [
                'user_id' => auth()->id(),
                'connection_id' => $connection->id,
                'zoom_email' => $userInfo['email'] ?? 'unknown',
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('success', 'Zoom connection is working! Connected as: ' . ($userInfo['email'] ?? 'Unknown'));

        } catch (\Exception $e) {
            Log::error('Zoom connection test failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Zoom connection test failed: ' . $e->getMessage());
        }
    }
}
