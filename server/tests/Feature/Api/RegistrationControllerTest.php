<?php

namespace Tests\Feature\Api;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $secret = 'test-registration-secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        config(['ned.registration_secret' => $this->secret]);
    }

    // ── Register ──────────────────────────────────────────────────────

    public function test_register_creates_new_server(): void
    {
        $response = $this->postJson('/api/servers/register', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['server_id', 'name', 'token', 'rotated']);
        $response->assertJson(['name' => 'worker-i-abc123', 'rotated' => false]);
        $this->assertDatabaseHas('servers', ['name' => 'worker-i-abc123', 'is_active' => true]);
    }

    public function test_register_rotates_token_for_existing_server(): void
    {
        $token = Server::generateToken();
        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'worker-i-abc123',
            'token' => $token['hashed'],
            'status' => 'offline',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/servers/register', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['rotated' => true]);
        $this->assertTrue($server->fresh()->is_active);
    }

    public function test_register_rejects_invalid_secret(): void
    {
        $response = $this->postJson('/api/servers/register', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);
    }

    public function test_register_rejects_missing_secret(): void
    {
        $response = $this->postJson('/api/servers/register', [
            'name' => 'worker-i-abc123',
        ]);

        $response->assertStatus(401);
    }

    // ── Deregister ────────────────────────────────────────────────────

    public function test_deregister_deactivates_server(): void
    {
        $token = Server::generateToken();
        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'worker-i-abc123',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/servers/deregister', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'server_id' => $server->id,
            'name' => 'worker-i-abc123',
            'deregistered' => true,
        ]);

        $fresh = $server->fresh();
        $this->assertFalse($fresh->is_active);
        $this->assertEquals('offline', $fresh->status);
    }

    public function test_deregister_returns_404_for_unknown_server(): void
    {
        $response = $this->postJson('/api/servers/deregister', [
            'name' => 'nonexistent-server',
        ], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(404);
    }

    public function test_deregister_rejects_invalid_secret(): void
    {
        $response = $this->postJson('/api/servers/deregister', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);
    }

    public function test_deregister_rejects_missing_name(): void
    {
        $response = $this->postJson('/api/servers/deregister', [], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(422);
    }

    public function test_deregister_is_idempotent(): void
    {
        $token = Server::generateToken();
        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'worker-i-abc123',
            'token' => $token['hashed'],
            'status' => 'offline',
            'is_active' => false,
        ]);

        // Deregistering an already-deregistered server should succeed (ASG retry safety)
        $response = $this->postJson('/api/servers/deregister', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['deregistered' => true]);
        $this->assertFalse($server->fresh()->is_active);
    }

    public function test_deregistered_server_can_reregister(): void
    {
        $token = Server::generateToken();
        Server::create([
            'user_id' => $this->user->id,
            'name' => 'worker-i-abc123',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        // Deregister
        $this->postJson('/api/servers/deregister', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => $this->secret,
        ])->assertStatus(200);

        // Re-register
        $response = $this->postJson('/api/servers/register', [
            'name' => 'worker-i-abc123',
        ], [
            'X-Registration-Secret' => $this->secret,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['rotated' => true]);

        $server = Server::where('name', 'worker-i-abc123')->first();
        $this->assertTrue($server->is_active);
    }
}
