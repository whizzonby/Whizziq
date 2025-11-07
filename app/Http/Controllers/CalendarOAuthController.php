<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Services\Calendar\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalendarOAuthController extends Controller
{
    protected ?GoogleCalendarService $googleCalendarService = null;

    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function getGoogleCalendarService(): GoogleCalendarService
    {
        if ($this->googleCalendarService === null) {
            $this->googleCalendarService = new GoogleCalendarService();
        }
        return $this->googleCalendarService;
    }

    /**
     * Redirect to Google Calendar OAuth authorization
     */
    public function redirectToGoogle(Request $request)
    {
        $service = $this->getGoogleCalendarService();
        
        if (!$service->isConfigured()) {
            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Google Calendar is not configured. Please add OAuth credentials to your .env file.');
        }

        // Generate state token for CSRF protection
        $state = Str::random(40);
        session(['google_calendar_oauth_state' => $state]);

        $authUrl = $service->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Google Calendar
     */
    public function handleGoogleCallback(Request $request)
    {
        // Check for errors
        if ($request->has('error')) {
            Log::warning('Google Calendar OAuth error', [
                'error' => $request->input('error'),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Google Calendar connection was cancelled or failed.');
        }

        // Verify state token (CSRF protection)
        $state = $request->input('state');
        $sessionState = session('google_calendar_oauth_state');

        if (!$state || $state !== $sessionState) {
            Log::error('Google Calendar OAuth state mismatch', [
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        // Clear state from session
        session()->forget('google_calendar_oauth_state');

        // Get authorization code
        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'No authorization code received from Google.');
        }

        try {
            // Exchange code for tokens
            $service = $this->getGoogleCalendarService();
            $tokenData = $service->exchangeCode($code);

            if (!$tokenData) {
                throw new \Exception('Failed to exchange authorization code for tokens');
            }

            // Get user's Google account info
            $userInfo = $this->fetchGoogleUserInfo($tokenData['access_token']);

            // Check if user already has a primary calendar connection
            $existingPrimary = CalendarConnection::where('user_id', auth()->id())
                ->where('provider', 'google_calendar')
                ->where('is_primary', true)
                ->exists();

            // Save or update the calendar connection
            $connection = CalendarConnection::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'provider' => 'google_calendar',
                    'provider_user_id' => $userInfo['id'] ?? null,
                ],
                [
                    'provider_email' => $userInfo['email'] ?? null,
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                    'is_primary' => !$existingPrimary, // First connection becomes primary
                    'sync_enabled' => true,
                    'calendar_id' => 'primary', // Use primary calendar by default
                ]
            );

            Log::info('Google Calendar connected successfully', [
                'user_id' => auth()->id(),
                'connection_id' => $connection->id,
                'email' => $userInfo['email'] ?? 'unknown',
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('success', 'Google Calendar connected successfully! Your events will now sync automatically.');

        } catch (\Exception $e) {
            Log::error('Google Calendar OAuth callback error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Failed to connect Google Calendar: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Google Calendar
     */
    public function disconnectGoogle(Request $request)
    {
        try {
            $connectionId = $request->input('connection_id');

            if ($connectionId) {
                // Disconnect specific connection
                $connection = CalendarConnection::where('id', $connectionId)
                    ->where('user_id', auth()->id())
                    ->first();
            } else {
                // Disconnect primary connection
                $connection = CalendarConnection::where('user_id', auth()->id())
                    ->where('provider', 'google_calendar')
                    ->where('is_primary', true)
                    ->first();
            }

            if ($connection) {
                $connection->delete();

                Log::info('Google Calendar disconnected', [
                    'user_id' => auth()->id(),
                    'connection_id' => $connection->id,
                ]);

                return redirect()->route('filament.dashboard.pages.booking-settings-page')
                    ->with('success', 'Google Calendar disconnected successfully.');
            }

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Calendar connection not found.');

        } catch (\Exception $e) {
            Log::error('Google Calendar disconnect error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Failed to disconnect calendar: ' . $e->getMessage());
        }
    }

    /**
     * Test Google Calendar connection
     */
    public function testConnection(Request $request)
    {
        try {
            $connection = CalendarConnection::where('user_id', auth()->id())
                ->where('provider', 'google_calendar')
                ->where('is_primary', true)
                ->firstOrFail();

            // Try to fetch calendar list to test the connection
            $service = $this->getGoogleCalendarService();
            $calendars = $service->getCalendarList($connection);

            if ($calendars === null) {
                throw new \Exception('Failed to fetch calendar list. Token may be invalid.');
            }

            Log::info('Google Calendar connection test successful', [
                'user_id' => auth()->id(),
                'connection_id' => $connection->id,
                'calendars_count' => count($calendars),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('success', 'Google Calendar connection is working! Found ' . count($calendars) . ' calendar(s).');

        } catch (\Exception $e) {
            Log::error('Google Calendar connection test failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('filament.dashboard.pages.booking-settings-page')
                ->with('error', 'Calendar connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Fetch Google user info
     */
    protected function fetchGoogleUserInfo(string $accessToken): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google user info', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
