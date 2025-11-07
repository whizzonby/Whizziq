<?php

use App\Http\Controllers\SocialMediaOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing OAuth Routes
|--------------------------------------------------------------------------
|
| Routes for handling OAuth callbacks from social media platforms
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/marketing/oauth/{platform}/callback', [SocialMediaOAuthController::class, 'callback'])
        ->name('marketing.oauth.callback');
});
