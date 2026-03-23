<?php

use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Middleware\AuthenticateServer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

// Rate limit: 5 registration attempts per minute per IP
RateLimiter::for('registration', function ($request) {
    return Limit::perMinute(5)->by($request->ip());
});

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

// Server auto-registration - shared secret + rate limited
Route::post('/servers/register', [RegistrationController::class, 'register'])
    ->middleware('throttle:registration');

// Agent endpoints - require server token
Route::middleware(AuthenticateServer::class)->group(function () {
    Route::post('/metrics', [MetricsController::class, 'store']);
});
