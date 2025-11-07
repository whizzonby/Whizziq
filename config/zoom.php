<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zoom OAuth Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used to authenticate with Zoom's API.
    | Get these from: https://marketplace.zoom.us/develop/create
    |
    */

    'account_id' => env('ZOOM_ACCOUNT_ID'),
    'client_id' => env('ZOOM_CLIENT_ID'),
    'client_secret' => env('ZOOM_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Zoom API Endpoints
    |--------------------------------------------------------------------------
    */

    'api_url' => 'https://api.zoom.us/v2',
    'oauth_url' => 'https://zoom.us/oauth',

    /*
    |--------------------------------------------------------------------------
    | Default Meeting Settings
    |--------------------------------------------------------------------------
    */

    'meeting_settings' => [
        'host_video' => true,
        'participant_video' => true,
        'join_before_host' => env('ZOOM_JOIN_BEFORE_HOST', false),
        'mute_upon_entry' => false,
        'waiting_room' => env('ZOOM_WAITING_ROOM', true),
        'auto_recording' => env('ZOOM_AUTO_RECORDING', 'none'), // none, local, cloud
        'approval_type' => 2, // 0 = automatically approve, 1 = manually approve, 2 = no registration required
    ],

    /*
    |--------------------------------------------------------------------------
    | Meeting Duration
    |--------------------------------------------------------------------------
    |
    | Default meeting duration in minutes
    |
    */

    'default_duration' => env('ZOOM_MEETING_DURATION_DEFAULT', 30),

];
