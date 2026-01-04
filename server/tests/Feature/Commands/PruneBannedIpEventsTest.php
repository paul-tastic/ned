<?php

namespace Tests\Feature\Commands;

use App\Models\BannedIpEvent;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneBannedIpEventsTest extends TestCase
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

    public function test_prunes_events_older_than_one_year(): void
    {
        // Old event (2 years ago)
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now()->subDays(730),
        ]);

        // Recent event (30 days ago)
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '5.6.7.8',
            'event_type' => 'ban',
            'event_at' => now()->subDays(30),
        ]);

        $this->artisan('ned:prune-banned-ips')
            ->expectsOutputToContain('Pruning 1 banned IP events')
            ->assertSuccessful();

        $this->assertDatabaseCount('banned_ip_events', 1);
        $this->assertDatabaseHas('banned_ip_events', ['ip_address' => '5.6.7.8']);
        $this->assertDatabaseMissing('banned_ip_events', ['ip_address' => '1.2.3.4']);
    }

    public function test_respects_custom_days_option(): void
    {
        // 100 days old
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now()->subDays(100),
        ]);

        // 50 days old
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '5.6.7.8',
            'event_type' => 'ban',
            'event_at' => now()->subDays(50),
        ]);

        $this->artisan('ned:prune-banned-ips', ['--days' => 60])
            ->assertSuccessful();

        $this->assertDatabaseCount('banned_ip_events', 1);
        $this->assertDatabaseHas('banned_ip_events', ['ip_address' => '5.6.7.8']);
    }

    public function test_handles_no_events_to_prune(): void
    {
        // Recent event only
        BannedIpEvent::create([
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
            'event_at' => now(),
        ]);

        $this->artisan('ned:prune-banned-ips')
            ->expectsOutputToContain('No old banned IP events to prune')
            ->assertSuccessful();

        $this->assertDatabaseCount('banned_ip_events', 1);
    }

    public function test_handles_empty_table(): void
    {
        $this->artisan('ned:prune-banned-ips')
            ->expectsOutputToContain('No old banned IP events to prune')
            ->assertSuccessful();
    }
}
