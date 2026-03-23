<?php

use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\RegistrationController;
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
    // Check multiple possible locations for VERSION file
    $locations = [
        base_path('VERSION'),           // In server directory
        base_path('../VERSION'),        // In repo root (if server is subdir)
    ];

    $version = 'unknown';
    foreach ($locations as $path) {
        if (file_exists($path)) {
            $version = trim(file_get_contents($path));
            break;
        }
    }

    return response()->json([
        'version' => $version,
        'app' => 'ned-server',
    ]);
});

// Server auto-registration - shared secret + rate limited (5 per minute)
Route::post('/servers/register', [RegistrationController::class, 'register'])
    ->middleware('throttle:5,1');

// Server deregistration - shared secret + rate limited (5 per minute)
Route::post('/servers/deregister', [RegistrationController::class, 'deregister'])
    ->middleware('throttle:5,1');

// Agent endpoints - require server token
Route::middleware(AuthenticateServer::class)->group(function () {
    Route::post('/metrics', [MetricsController::class, 'store']);
});
