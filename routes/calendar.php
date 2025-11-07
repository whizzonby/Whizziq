<?php

use App\Http\Controllers\CalendarOAuthController;
use App\Http\Controllers\ZoomOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Calendar OAuth Routes
|--------------------------------------------------------------------------
|
| Routes for handling OAuth flows for calendar integrations
| (Google Calendar, Outlook, etc.)
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    // Google Calendar OAuth routes
    Route::get('/calendar/google/connect', [CalendarOAuthController::class, 'redirectToGoogle'])
        ->name('calendar.google.connect');

    Route::get('/calendar/google/callback', [CalendarOAuthController::class, 'handleGoogleCallback'])
        ->name('calendar.google.callback');

    Route::post('/calendar/google/disconnect', [CalendarOAuthController::class, 'disconnectGoogle'])
        ->name('calendar.google.disconnect');

    Route::get('/calendar/google/test', [CalendarOAuthController::class, 'testConnection'])
        ->name('calendar.google.test');

    // Zoom OAuth routes
    Route::get('/zoom/connect', [ZoomOAuthController::class, 'redirectToZoom'])
        ->name('zoom.connect');

    Route::get('/zoom/callback', [ZoomOAuthController::class, 'handleZoomCallback'])
        ->name('zoom.callback');

    Route::post('/zoom/disconnect', [ZoomOAuthController::class, 'disconnectZoom'])
        ->name('zoom.disconnect');

    Route::get('/zoom/test', [ZoomOAuthController::class, 'testConnection'])
        ->name('zoom.test');
});
