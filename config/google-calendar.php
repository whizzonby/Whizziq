<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Calendar OAuth Credentials
    |--------------------------------------------------------------------------
    |
    | Get these from: https://console.cloud.google.com/
    | Enable Google Calendar API and create OAuth 2.0 credentials
    |
    */

    'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI', env('APP_URL') . '/oauth/google-calendar/callback'),

    /*
    |--------------------------------------------------------------------------
    | Google API Endpoints
    |--------------------------------------------------------------------------
    */

    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'api_url' => 'https://www.googleapis.com/calendar/v3',

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    |
    | Permissions requested from Google
    |
    */

    'scopes' => [
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'enabled' => env('GOOGLE_CALENDAR_SYNC_ENABLED', true),
        'interval_minutes' => env('GOOGLE_CALENDAR_SYNC_INTERVAL', 15),
        'days_ahead' => env('GOOGLE_CALENDAR_SYNC_DAYS_AHEAD', 60),
        'days_behind' => env('GOOGLE_CALENDAR_SYNC_DAYS_BEHIND', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Settings
    |--------------------------------------------------------------------------
    */

    'event' => [
        'send_updates' => 'all', // all, externalOnly, none
        'conference_solution' => 'hangoutsMeet', // Google Meet
    ],

];
