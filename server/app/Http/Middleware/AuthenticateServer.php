<?php

namespace App\Http\Middleware;

use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateServer
{
    /**
     * Handle an incoming request.
     * Validates the Bearer token and attaches the server to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'error' => 'Missing authentication token',
            ], 401);
        }

        $server = Server::findByToken($token);

        if (! $server) {
            return response()->json([
                'error' => 'Invalid authentication token',
            ], 401);
        }

        if (! $server->is_active) {
            return response()->json([
                'error' => 'Server is deactivated',
            ], 403);
        }

        // Attach server to request for use in controllers
        $request->attributes->set('server', $server);

        return $next($request);
    }
}
