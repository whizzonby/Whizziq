<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected Client $client;
    protected string $authUrl;
    protected string $tokenUrl;
    protected string $apiUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $redirectUri;
    protected array $scopes;

    public function __construct()
    {
        $this->authUrl = config('google-calendar.auth_url') ?? '';
        $this->tokenUrl = config('google-calendar.token_url') ?? '';
        $this->apiUrl = config('google-calendar.api_url') ?? '';
        $this->clientId = config('google-calendar.client_id');
        $this->clientSecret = config('google-calendar.client_secret');
        $this->redirectUri = config('google-calendar.redirect_uri');
        $this->scopes = config('google-calendar.scopes') ?? [];

        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // Set to true in production
        ]);
    }

    /**
     * Generate OAuth authorization URL
     */
    public function getAuthorizationUrl(string $state = null): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'access_type' => 'offline', // Get refresh token
            'prompt' => 'consent', // Force consent to get refresh token
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return $this->authUrl . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCode(string $code): ?array
    {
        try {
            $response = $this->client->post($this->tokenUrl, [
                'form_params' => [
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type'],
                'scope' => $data['scope'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('Google OAuth token exchange error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $response = $this->client->post($this->tokenUrl, [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type'],
            ];
        } catch (GuzzleException $e) {
            Log::error('Google token refresh error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Ensure token is valid, refresh if needed
     */
    protected function ensureValidToken(CalendarConnection $connection): ?string
    {
        if ($connection->needsTokenRefresh()) {
            $newTokenData = $this->refreshToken($connection->refresh_token);

            if ($newTokenData) {
                $connection->update([
                    'access_token' => $newTokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($newTokenData['expires_in']),
                ]);

                return $newTokenData['access_token'];
            }

            return null;
        }

        return $connection->access_token;
    }

    /**
     * Create a calendar event with Google Meet
     */
    public function createEvent(CalendarConnection $connection, array $eventData): ?array
    {
        $token = $this->ensureValidToken($connection);

        if (!$token) {
            Log::error('Cannot create Google Calendar event: Invalid token');
            return null;
        }

        try {
            $calendarId = $connection->calendar_id ?? 'primary';

            $response = $this->client->post($this->apiUrl . '/calendars/' . $calendarId . '/events', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'conferenceDataVersion' => 1, // Enable conference data (Google Meet)
                ],
                'json' => $this->prepareEventData($eventData),
            ]);

            $event = json_decode($response->getBody()->getContents(), true);

            return [
                'id' => $event['id'],
                'html_link' => $event['htmlLink'],
                'hangout_link' => $event['hangoutLink'] ?? null,
                'meet_link' => $event['conferenceData']['entryPoints'][0]['uri'] ?? $event['hangoutLink'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('Google Calendar create event error', [
                'error' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }

    /**
     * Update a calendar event
     */
    public function updateEvent(CalendarConnection $connection, string $eventId, array $eventData): bool
    {
        $token = $this->ensureValidToken($connection);

        if (!$token) {
            return false;
        }

        try {
            $calendarId = $connection->calendar_id ?? 'primary';

            $this->client->patch($this->apiUrl . '/calendars/' . $calendarId . '/events/' . $eventId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $this->prepareEventData($eventData),
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::error('Google Calendar update event error', [
                'error' => $e->getMessage(),
                'event_id' => $eventId,
            ]);
            return false;
        }
    }

    /**
     * Delete a calendar event
     */
    public function deleteEvent(CalendarConnection $connection, string $eventId): bool
    {
        $token = $this->ensureValidToken($connection);

        if (!$token) {
            return false;
        }

        try {
            $calendarId = $connection->calendar_id ?? 'primary';

            $this->client->delete($this->apiUrl . '/calendars/' . $calendarId . '/events/' . $eventId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::error('Google Calendar delete event error', [
                'error' => $e->getMessage(),
                'event_id' => $eventId,
            ]);
            return false;
        }
    }

    /**
     * List calendar events (for sync)
     */
    public function listEvents(CalendarConnection $connection, array $options = []): ?array
    {
        $token = $this->ensureValidToken($connection);

        if (!$token) {
            return null;
        }

        try {
            $calendarId = $connection->calendar_id ?? 'primary';

            $params = [
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];

            // Time range for sync
            if (isset($options['timeMin'])) {
                $params['timeMin'] = $options['timeMin'];
            } else {
                $params['timeMin'] = now()->subDays(config('google-calendar.sync.days_behind', 7))->toRfc3339String();
            }

            if (isset($options['timeMax'])) {
                $params['timeMax'] = $options['timeMax'];
            } else {
                $params['timeMax'] = now()->addDays(config('google-calendar.sync.days_ahead', 60))->toRfc3339String();
            }

            // Use sync token for incremental sync
            if ($connection->sync_token && !isset($options['fullSync'])) {
                $params['syncToken'] = $connection->sync_token;
                unset($params['timeMin'], $params['timeMax']); // Can't use with sync token
            }

            $response = $this->client->get($this->apiUrl . '/calendars/' . $calendarId . '/events', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Save new sync token for next incremental sync
            if (isset($data['nextSyncToken'])) {
                $connection->update(['sync_token' => $data['nextSyncToken']]);
            }

            return $data['items'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Google Calendar list events error', [
                'error' => $e->getMessage(),
            ]);

            // If sync token is invalid, do a full sync
            if (str_contains($e->getMessage(), 'Sync token is no longer valid')) {
                $connection->update(['sync_token' => null]);
                return $this->listEvents($connection, array_merge($options, ['fullSync' => true]));
            }

            return null;
        }
    }

    /**
     * Prepare event data for Google Calendar API
     */
    protected function prepareEventData(array $data): array
    {
        $event = [
            'summary' => $data['summary'] ?? 'Event',
            'description' => $data['description'] ?? '',
            'start' => [
                'dateTime' => $data['start_time'],
                'timeZone' => $data['timezone'] ?? config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $data['end_time'],
                'timeZone' => $data['timezone'] ?? config('app.timezone'),
            ],
        ];

        // Add attendees if provided
        if (!empty($data['attendees'])) {
            $event['attendees'] = array_map(function ($email) {
                return ['email' => $email];
            }, $data['attendees']);
        }

        // Add Google Meet conference
        if ($data['add_meet'] ?? true) {
            $event['conferenceData'] = [
                'createRequest' => [
                    'requestId' => uniqid('meet_'),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ];
        }

        return $event;
    }

    /**
     * Get user's calendar list
     */
    public function getCalendarList(CalendarConnection $connection): ?array
    {
        $token = $this->ensureValidToken($connection);

        if (!$token) {
            return null;
        }

        try {
            $response = $this->client->get($this->apiUrl . '/users/me/calendarList', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['items'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Google Calendar get calendar list error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if Google Calendar is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) &&
               !empty($this->clientSecret) &&
               !empty($this->redirectUri);
    }
}
