<?php

namespace Tests\Unit\Models;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_generate_token_returns_plain_and_hashed(): void
    {
        $token = Server::generateToken();

        $this->assertArrayHasKey('plain', $token);
        $this->assertArrayHasKey('hashed', $token);
        $this->assertEquals(64, strlen($token['plain']));
        $this->assertEquals(64, strlen($token['hashed'])); // SHA256 hex is 64 chars
    }

    public function test_find_by_token_returns_server_with_valid_token(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $found = Server::findByToken($token['plain']);

        $this->assertNotNull($found);
        $this->assertEquals($server->id, $found->id);
    }

    public function test_find_by_token_returns_null_with_invalid_token(): void
    {
        $token = Server::generateToken();

        Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $found = Server::findByToken('invalid-token');

        $this->assertNull($found);
    }

    public function test_mark_as_seen_updates_timestamp(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $this->assertNull($server->last_seen_at);

        $server->markAsSeen();

        $this->assertNotNull($server->fresh()->last_seen_at);
    }

    public function test_update_status_changes_status(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $server->updateStatus('warning');

        $this->assertEquals('warning', $server->fresh()->status);
    }

    public function test_is_offline_returns_true_when_never_seen(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $this->assertTrue($server->isOffline());
    }

    public function test_is_offline_returns_true_when_not_seen_in_5_minutes(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
            'last_seen_at' => now()->subMinutes(10),
        ]);

        $this->assertTrue($server->isOffline());
    }

    public function test_is_offline_returns_false_when_recently_seen(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
            'last_seen_at' => now()->subMinutes(2),
        ]);

        $this->assertFalse($server->isOffline());
    }

    public function test_latest_metric_returns_most_recent(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        Metric::create([
            'server_id' => $server->id,
            'recorded_at' => now()->subHour(),
            'load_1m' => 1.0,
        ]);

        $latest = Metric::create([
            'server_id' => $server->id,
            'recorded_at' => now(),
            'load_1m' => 2.0,
        ]);

        $this->assertEquals($latest->id, $server->latestMetric()->id);
        $this->assertEquals(2.0, $server->latestMetric()->load_1m);
    }

    public function test_server_belongs_to_user(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(User::class, $server->user);
        $this->assertEquals($this->user->id, $server->user->id);
    }

    public function test_server_has_many_metrics(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        Metric::create(['server_id' => $server->id, 'recorded_at' => now()]);
        Metric::create(['server_id' => $server->id, 'recorded_at' => now()]);
        Metric::create(['server_id' => $server->id, 'recorded_at' => now()]);

        $this->assertCount(3, $server->metrics);
    }

    public function test_token_is_hidden_from_serialization(): void
    {
        $token = Server::generateToken();

        $server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        $array = $server->toArray();

        $this->assertArrayNotHasKey('token', $array);
    }
}
