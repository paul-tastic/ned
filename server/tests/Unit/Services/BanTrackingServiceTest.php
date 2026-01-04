<?php

namespace Tests\Unit\Services;

use App\Models\BannedIpEvent;
use App\Models\Server;
use App\Models\User;
use App\Services\BanTrackingService;
use App\Services\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BanTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BanTrackingService $service;

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
        $this->service = app(BanTrackingService::class);
    }

    public function test_first_banned_ips_are_recorded_as_bans(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Russia',
                'countryCode' => 'RU',
                'city' => 'Moscow',
                'isp' => 'Bad ISP',
            ]),
        ]);

        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4', '5.6.7.8'],
        ]);

        $this->assertDatabaseCount('banned_ip_events', 2);
        $this->assertDatabaseHas('banned_ip_events', [
            'server_id' => $this->server->id,
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
        ]);
        $this->assertDatabaseHas('banned_ip_events', [
            'server_id' => $this->server->id,
            'ip_address' => '5.6.7.8',
            'event_type' => 'ban',
        ]);
    }

    public function test_repeated_banned_ips_are_not_duplicated(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success'])]);

        // First report
        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        // Second report with same IP still banned
        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        // Should only have one ban event
        $this->assertDatabaseCount('banned_ip_events', 1);
    }

    public function test_unban_is_recorded_when_ip_removed_from_list(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success'])]);

        // First report with banned IP
        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        // Second report without the IP (unbanned)
        $this->service->processBannedIps($this->server, [
            'banned_ips' => [],
        ]);

        $this->assertDatabaseCount('banned_ip_events', 2);
        $this->assertDatabaseHas('banned_ip_events', [
            'ip_address' => '1.2.3.4',
            'event_type' => 'ban',
        ]);
        $this->assertDatabaseHas('banned_ip_events', [
            'ip_address' => '1.2.3.4',
            'event_type' => 'unban',
        ]);
    }

    public function test_reban_after_unban_creates_new_ban_event(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success'])]);

        // Ban
        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        // Unban
        $this->service->processBannedIps($this->server, [
            'banned_ips' => [],
        ]);

        // Re-ban
        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        // Should have: ban, unban, ban
        $this->assertDatabaseCount('banned_ip_events', 3);
        $this->assertEquals(2, BannedIpEvent::getBanCount($this->server->id, '1.2.3.4'));
    }

    public function test_handles_new_format_with_unban_at(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success'])]);

        $this->service->processBannedIps($this->server, [
            'banned_ips' => [
                ['ip' => '1.2.3.4', 'unban_at' => '2025-01-03T12:00:00Z'],
                ['ip' => '5.6.7.8', 'unban_at' => '2025-01-03T13:00:00Z'],
            ],
        ]);

        $this->assertDatabaseCount('banned_ip_events', 2);
        $this->assertDatabaseHas('banned_ip_events', ['ip_address' => '1.2.3.4']);
        $this->assertDatabaseHas('banned_ip_events', ['ip_address' => '5.6.7.8']);
    }

    public function test_stores_geo_data_on_ban(): void
    {
        Http::fake([
            'ip-api.com/json/1.2.3.4*' => Http::response([
                'status' => 'success',
                'country' => 'Germany',
                'countryCode' => 'DE',
                'city' => 'Berlin',
                'isp' => 'Deutsche Telekom',
            ]),
        ]);

        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        $this->assertDatabaseHas('banned_ip_events', [
            'ip_address' => '1.2.3.4',
            'country' => 'Germany',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'isp' => 'Deutsche Telekom',
        ]);
    }

    public function test_preserves_geo_data_from_ban_on_unban(): void
    {
        Http::fake([
            'ip-api.com/json/1.2.3.4*' => Http::response([
                'status' => 'success',
                'country' => 'France',
                'countryCode' => 'FR',
                'city' => 'Paris',
                'isp' => 'Orange',
            ]),
        ]);

        // Ban
        $this->service->processBannedIps($this->server, [
            'banned_ips' => ['1.2.3.4'],
        ]);

        // Unban
        $this->service->processBannedIps($this->server, [
            'banned_ips' => [],
        ]);

        // Unban event should have same geo as ban
        $unbanEvent = BannedIpEvent::where('event_type', 'unban')->first();
        $this->assertEquals('France', $unbanEvent->country);
        $this->assertEquals('FR', $unbanEvent->country_code);
        $this->assertEquals('Paris', $unbanEvent->city);
    }

    public function test_empty_banned_ips_does_nothing(): void
    {
        $this->service->processBannedIps($this->server, [
            'banned_ips' => [],
        ]);

        $this->assertDatabaseCount('banned_ip_events', 0);
    }

    public function test_null_banned_ips_does_nothing(): void
    {
        $this->service->processBannedIps($this->server, []);

        $this->assertDatabaseCount('banned_ip_events', 0);
    }
}
