<?php

namespace Tests\Unit\Models;

use App\Models\BannedIpEvent;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannedIpEventTest extends TestCase
{
    use RefreshDatabase;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $token = Server::generateToken();
        $this->server = Server::create([
            'user_id' => $user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);
    }

    public function test_get_ban_count_returns_zero_for_unknown_ip(): void
    {
        $count = BannedIpEvent::getBanCount($this->server->id, '1.2.3.4');

        $this->assertEquals(0, $count);
    }

    public function test_get_ban_count_returns_correct_count(): void
    {
        // Create 3 ban events for same IP
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now()->subDays(30),
        ]);
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'unban',
            'event_at' => now()->subDays(29),
        ]);
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now()->subDays(20),
        ]);
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'unban',
            'event_at' => now()->subDays(19),
        ]);
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now(),
        ]);

        // Should count only ban events (not unbans)
        $count = BannedIpEvent::getBanCount($this->server->id, '1.2.3.4');

        $this->assertEquals(3, $count);
    }

    public function test_get_ban_counts_returns_counts_for_multiple_ips(): void
    {
        // IP 1 banned 2 times
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.1.1.1',
            'event_type' => 'ban',
            'event_at' => now()->subDays(5),
        ]);
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.1.1.1',
            'event_type' => 'ban',
            'event_at' => now(),
        ]);

        // IP 2 banned 5 times
        for ($i = 0; $i < 5; $i++) {
            BannedIpEvent::create([
                'server_id' => $this->server->id,
                'ip_address' => '2.2.2.2',
                'event_type' => 'ban',
                'event_at' => now()->subDays($i),
            ]);
        }

        $counts = BannedIpEvent::getBanCounts($this->server->id, ['1.1.1.1', '2.2.2.2', '3.3.3.3']);

        $this->assertEquals(2, $counts['1.1.1.1']);
        $this->assertEquals(5, $counts['2.2.2.2']);
        $this->assertArrayNotHasKey('3.3.3.3', $counts); // Never banned
    }

    public function test_get_ban_counts_returns_empty_array_for_empty_input(): void
    {
        $counts = BannedIpEvent::getBanCounts($this->server->id, []);

        $this->assertIsArray($counts);
        $this->assertEmpty($counts);
    }

    public function test_ban_count_is_scoped_to_server(): void
    {
        $otherUser = User::factory()->create();
        $otherToken = Server::generateToken();
        $otherServer = Server::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Server',
            'token' => $otherToken['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);

        // Ban on server 1
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now(),
        ]);

        // Ban on server 2
        BannedIpEvent::create([
            'server_id' => $otherServer->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now(),
        ]);

        // Each server should only see its own bans
        $this->assertEquals(1, BannedIpEvent::getBanCount($this->server->id, '1.2.3.4'));
        $this->assertEquals(1, BannedIpEvent::getBanCount($otherServer->id, '1.2.3.4'));
    }
}
