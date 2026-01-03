<?php

use App\Http\Controllers\Api\MetricsController;
use App\Http\Middleware\AuthenticateServer;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Agent endpoints (server token auth)
|
*/

// Health check - no auth required
Route::get('/health', [MetricsController::class, 'health']);

// Agent endpoints - require server token
Route::middleware(AuthenticateServer::class)->group(function () {
    Route::post('/metrics', [MetricsController::class, 'store']);
});
