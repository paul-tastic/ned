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

// Version endpoint - no auth required
Route::get('/version', function () {
    $versionFile = base_path('../VERSION');
    $version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'unknown';

    return response()->json([
        'version' => $version,
        'app' => 'ned-server',
    ]);
});

// Agent endpoints - require server token
Route::middleware(AuthenticateServer::class)->group(function () {
    Route::post('/metrics', [MetricsController::class, 'store']);
});
