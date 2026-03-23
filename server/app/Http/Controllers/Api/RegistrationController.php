<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    /**
     * Auto-register a server and return an agent token.
     * POST /api/servers/register
     *
     * Headers:
     *   X-Registration-Secret: <shared secret from NED_REGISTRATION_SECRET env>
     *
     * Body:
     *   name: string (required) — e.g. "worker-i-0abc123"
     *   group: string (optional) — e.g. "worker", "webapp"
     *   hostname: string (optional)
     */
    public function register(Request $request): JsonResponse
    {
        $secret = config('ned.registration_secret');

        if (! $secret || $request->header('X-Registration-Secret') !== $secret) {
            return response()->json(['error' => 'Invalid registration secret'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group' => 'nullable|string|max:100',
            'hostname' => 'nullable|string|max:255',
        ]);

        // Check if a server with this name already exists — return existing token info
        $existing = Server::where('name', $validated['name'])->first();
        if ($existing) {
            // Rotate the token so the caller gets a fresh one
            $token = Server::generateToken();
            $existing->update(['token' => $token['hashed'], 'is_active' => true]);

            return response()->json([
                'server_id' => $existing->id,
                'name' => $existing->name,
                'token' => $token['plain'],
                'rotated' => true,
            ], 200);
        }

        // Safety cap — prevent runaway server creation
        $maxServers = config('ned.max_servers', 50);
        if (Server::count() >= $maxServers) {
            return response()->json([
                'error' => 'Server limit reached',
                'max' => $maxServers,
            ], 429);
        }

        // Assign to the first admin user
        $user = User::first();
        if (! $user) {
            return response()->json(['error' => 'No admin user configured'], 500);
        }

        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'hostname' => $validated['hostname'] ?? null,
            'token' => $token['hashed'],
            'status' => 'offline',
        ]);

        return response()->json([
            'server_id' => $server->id,
            'name' => $server->name,
            'token' => $token['plain'],
            'rotated' => false,
        ], 201);
    }

    /**
     * Deregister a server (soft-delete: deactivate + set offline).
     * POST /api/servers/deregister
     *
     * Headers:
     *   X-Registration-Secret: <shared secret from NED_REGISTRATION_SECRET env>
     *
     * Body:
     *   name: string (required) — e.g. "worker-i-0abc123"
     */
    public function deregister(Request $request): JsonResponse
    {
        $secret = config('ned.registration_secret');

        if (! $secret || $request->header('X-Registration-Secret') !== $secret) {
            return response()->json(['error' => 'Invalid registration secret'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $server = Server::where('name', $validated['name'])->first();

        if (! $server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        $server->update([
            'is_active' => false,
            'status' => 'offline',
        ]);

        return response()->json([
            'server_id' => $server->id,
            'name' => $server->name,
            'deregistered' => true,
        ]);
    }
}
