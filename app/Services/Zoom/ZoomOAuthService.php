<?php

namespace App\Services\Zoom;

use App\Models\CalendarConnection;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZoomOAuthService
{
    protected Client $client;
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $baseUrl = 'https://zoom.us';
    protected string $apiUrl = 'https://api.zoom.us/v2';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);

        $this->clientId = config('services.zoom.client_id') ?? '';
        $this->clientSecret = config('services.zoom.client_secret') ?? '';
        $this->redirectUri = config('services.zoom.redirect_uri') ?? route('zoom.callback');
    }

    /**
     * Check if Zoom OAuth is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get the OAuth authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ]);

        return $this->baseUrl . '/oauth/authorize?' . $params;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCode(string $code): array
    {
        try {
            $response = $this->client->post($this->baseUrl . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'],
                'scope' => $data['scope'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Zoom OAuth token exchange failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Refresh an expired access token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = $this->client->post($this->baseUrl . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'],
            ];
        } catch (\Exception $e) {
            Log::error('Zoom token refresh failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ensure the connection has a valid access token (refresh if needed)
     */
    public function ensureValidToken(CalendarConnection $connection): string
    {
        // Check if token is expired or will expire soon
        if ($connection->needsTokenRefresh()) {
            $tokenData = $this->refreshToken($connection->refresh_token);

            $connection->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);

            Log::info('Zoom token refreshed', [
                'connection_id' => $connection->id,
                'user_id' => $connection->user_id,
            ]);
        }

        return $connection->access_token;
    }

    /**
     * Get the authenticated user's Zoom profile
     */
    public function getUser(CalendarConnection $connection): ?array
    {
        try {
            $token = $this->ensureValidToken($connection);

            $response = $this->client->get($this->apiUrl . '/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Zoom user profile', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a Zoom meeting using user's OAuth token
     */
    public function createMeeting(CalendarConnection $connection, array $meetingData): ?array
    {
        try {
            $token = $this->ensureValidToken($connection);

            $response = $this->client->post($this->apiUrl . '/users/me/meetings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $meetingData,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Zoom meeting created via OAuth', [
                'connection_id' => $connection->id,
                'meeting_id' => $data['id'] ?? null,
                'user_id' => $connection->user_id,
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('Failed to create Zoom meeting via OAuth', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Update an existing Zoom meeting
     */
    public function updateMeeting(CalendarConnection $connection, string $meetingId, array $meetingData): bool
    {
        try {
            $token = $this->ensureValidToken($connection);

            $this->client->patch($this->apiUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $meetingData,
            ]);

            Log::info('Zoom meeting updated via OAuth', [
                'connection_id' => $connection->id,
                'meeting_id' => $meetingId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update Zoom meeting via OAuth', [
                'connection_id' => $connection->id,
                'meeting_id' => $meetingId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete a Zoom meeting
     */
    public function deleteMeeting(CalendarConnection $connection, string $meetingId): bool
    {
        try {
            $token = $this->ensureValidToken($connection);

            $this->client->delete($this->apiUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            Log::info('Zoom meeting deleted via OAuth', [
                'connection_id' => $connection->id,
                'meeting_id' => $meetingId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete Zoom meeting via OAuth', [
                'connection_id' => $connection->id,
                'meeting_id' => $meetingId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Revoke user's Zoom OAuth token
     */
    public function revokeToken(CalendarConnection $connection): bool
    {
        try {
            $this->client->post($this->baseUrl . '/oauth/revoke', [
                'form_params' => [
                    'token' => $connection->access_token,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
            ]);

            Log::info('Zoom OAuth token revoked', [
                'connection_id' => $connection->id,
                'user_id' => $connection->user_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke Zoom OAuth token', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
