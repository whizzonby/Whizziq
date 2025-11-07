<?php

namespace App\Services\Zoom;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZoomApiClient
{
    protected Client $client;
    protected string $accountId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $apiUrl;
    protected string $oauthUrl;

    public function __construct()
    {
        $this->accountId = config('zoom.account_id');
        $this->clientId = config('zoom.client_id');
        $this->clientSecret = config('zoom.client_secret');
        $this->apiUrl = config('zoom.api_url');
        $this->oauthUrl = config('zoom.oauth_url');

        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // For development, set to true in production
        ]);
    }

    /**
     * Get Server-to-Server OAuth access token
     */
    protected function getAccessToken(): ?string
    {
        // Cache the token for 55 minutes (tokens expire in 1 hour)
        return Cache::remember('zoom_s2s_token', 3300, function () {
            try {
                $response = $this->client->post($this->oauthUrl . '/token', [
                    'query' => [
                        'grant_type' => 'account_credentials',
                        'account_id' => $this->accountId,
                    ],
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return $data['access_token'] ?? null;
            } catch (GuzzleException $e) {
                Log::error('Zoom OAuth token error', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                return null;
            }
        });
    }

    /**
     * Create a Zoom meeting
     *
     * @param array $meetingData
     * @return array|null
     */
    public function createMeeting(array $meetingData): ?array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            Log::error('Cannot create Zoom meeting: No access token');
            return null;
        }

        try {
            $response = $this->client->post($this->apiUrl . '/users/me/meetings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $this->prepareMeetingData($meetingData),
            ]);

            $meeting = json_decode($response->getBody()->getContents(), true);

            return [
                'id' => $meeting['id'],
                'join_url' => $meeting['join_url'],
                'start_url' => $meeting['start_url'],
                'password' => $meeting['password'] ?? null,
                'meeting_id' => $meeting['id'],
            ];
        } catch (GuzzleException $e) {
            Log::error('Zoom create meeting error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'meeting_data' => $meetingData,
            ]);
            return null;
        }
    }

    /**
     * Update a Zoom meeting
     *
     * @param string $meetingId
     * @param array $meetingData
     * @return bool
     */
    public function updateMeeting(string $meetingId, array $meetingData): bool
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return false;
        }

        try {
            $this->client->patch($this->apiUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $this->prepareMeetingData($meetingData),
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::error('Zoom update meeting error', [
                'error' => $e->getMessage(),
                'meeting_id' => $meetingId,
            ]);
            return false;
        }
    }

    /**
     * Delete a Zoom meeting
     *
     * @param string $meetingId
     * @return bool
     */
    public function deleteMeeting(string $meetingId): bool
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return false;
        }

        try {
            $this->client->delete($this->apiUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::error('Zoom delete meeting error', [
                'error' => $e->getMessage(),
                'meeting_id' => $meetingId,
            ]);
            return false;
        }
    }

    /**
     * Get meeting details
     *
     * @param string $meetingId
     * @return array|null
     */
    public function getMeeting(string $meetingId): ?array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return null;
        }

        try {
            $response = $this->client->get($this->apiUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Zoom get meeting error', [
                'error' => $e->getMessage(),
                'meeting_id' => $meetingId,
            ]);
            return null;
        }
    }

    /**
     * Prepare meeting data for Zoom API
     *
     * @param array $data
     * @return array
     */
    protected function prepareMeetingData(array $data): array
    {
        $settings = config('zoom.meeting_settings');

        return [
            'topic' => $data['topic'] ?? 'Meeting',
            'type' => 2, // Scheduled meeting
            'start_time' => $data['start_time'] ?? now()->toIso8601String(),
            'duration' => $data['duration'] ?? config('zoom.default_duration'),
            'timezone' => $data['timezone'] ?? config('app.timezone'),
            'agenda' => $data['agenda'] ?? '',
            'settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * Check if Zoom is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->accountId) &&
               !empty($this->clientId) &&
               !empty($this->clientSecret);
    }

    /**
     * Test the connection to Zoom API
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return false;
        }

        try {
            // Try to get user info
            $response = $this->client->get($this->apiUrl . '/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Zoom connection test failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
