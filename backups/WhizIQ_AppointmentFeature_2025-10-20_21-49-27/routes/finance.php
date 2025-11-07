<?php

use App\Http\Controllers\FinanceOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finance OAuth Routes
|--------------------------------------------------------------------------
|
| Routes for handling OAuth callbacks from financial platforms
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/finance/oauth/{platform}/callback', [FinanceOAuthController::class, 'callback'])
        ->name('finance.oauth.callback');

    Route::get('/finance/download-template', [FinanceOAuthController::class, 'downloadTemplate'])
        ->name('filament.dashboard.resources.finances.download-template');
});
