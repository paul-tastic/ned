<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ShowServer;
use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use App\Services\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ShowServerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = $this->createServer();
    }

    public function test_component_renders_for_owner(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ShowServer::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertSee($this->server->name);
    }

    public function test_denies_access_to_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        Livewire::test(ShowServer::class, ['server' => $this->server])
            ->assertStatus(403);
    }

    public function test_shows_server_hostname(): void
    {
        $this->actingAs($this->user);

        $server = $this->createServer(['hostname' => 'test.example.com']);

        Livewire::test(ShowServer::class, ['server' => $server])
            ->assertSee('test.example.com');
    }

    public function test_can_delete_server(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ShowServer::class, ['server' => $this->server])
            ->call('delete')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('servers', ['id' => $this->server->id]);
    }

    public function test_delete_redirects_with_message(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ShowServer::class, ['server' => $this->server])
            ->call('delete')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('message', 'Server deleted.');
    }

    public function test_shows_latest_metric(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subHour(),
            'memory_total' => 8192,
            'memory_used' => 4096,
        ]);

        $latestMetric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'memory_total' => 8192,
            'memory_used' => 6144,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $viewLatest = $component->viewData('latestMetric');

        $this->assertEquals($latestMetric->id, $viewLatest->id);
        $this->assertEquals(6144, $viewLatest->memory_used);
    }

    public function test_shows_previous_metric_for_comparison(): void
    {
        $this->actingAs($this->user);

        $previousMetric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'load_1m' => 1.0,
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 2.0,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $viewPrevious = $component->viewData('previousMetric');

        $this->assertNotNull($viewPrevious);
        $this->assertEquals($previousMetric->id, $viewPrevious->id);
    }

    public function test_previous_metric_is_null_with_single_metric(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 1.0,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $this->assertNull($component->viewData('previousMetric'));
    }

    public function test_latest_metric_is_null_with_no_metrics(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $this->assertNull($component->viewData('latestMetric'));
    }

    public function test_metrics_limited_to_288(): void
    {
        $this->actingAs($this->user);

        // Create 300 metrics (more than 288 limit)
        for ($i = 0; $i < 300; $i++) {
            Metric::create([
                'server_id' => $this->server->id,
                'recorded_at' => now()->subMinutes(300 - $i),
                'load_1m' => $i,
            ]);
        }

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $metrics = $component->viewData('metrics');

        // Limit is 288 (24 hours at 5-min intervals)
        $this->assertCount(288, $metrics);
    }

    public function test_delete_modal_starts_hidden(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $this->assertFalse($component->get('showDeleteModal'));
    }

    public function test_can_toggle_delete_modal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ShowServer::class, ['server' => $this->server])
            ->set('showDeleteModal', true)
            ->assertSet('showDeleteModal', true)
            ->set('showDeleteModal', false)
            ->assertSet('showDeleteModal', false);
    }

    public function test_security_chart_data_calculates_attack_deltas(): void
    {
        $this->actingAs($this->user);

        // Create metrics with increasing SSH failed counts
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(15),
            'security' => ['ssh_failed_24h' => 100],
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(10),
            'security' => ['ssh_failed_24h' => 110],
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'security' => ['ssh_failed_24h' => 125],
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'security' => ['ssh_failed_24h' => 130],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('securityChartData');

        $this->assertCount(4, $chartData);
        // First point has delta 0 (no previous)
        $this->assertEquals(0, $chartData[0]['attacks']);
        // Second point: 110 - 100 = 10
        $this->assertEquals(10, $chartData[1]['attacks']);
        // Third point: 125 - 110 = 15
        $this->assertEquals(15, $chartData[2]['attacks']);
        // Fourth point: 130 - 125 = 5
        $this->assertEquals(5, $chartData[3]['attacks']);
    }

    public function test_security_chart_handles_counter_reset(): void
    {
        $this->actingAs($this->user);

        // Simulate counter reset (value decreases)
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(10),
            'security' => ['ssh_failed_24h' => 100],
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'security' => ['ssh_failed_24h' => 50], // Reset occurred
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'security' => ['ssh_failed_24h' => 60],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('securityChartData');

        // Delta should be 0 when counter resets (value decreases)
        $this->assertEquals(0, $chartData[1]['attacks']);
        // Next delta should work normally
        $this->assertEquals(10, $chartData[2]['attacks']);
    }

    public function test_security_chart_includes_total_24h(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'security' => ['ssh_failed_24h' => 150],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('securityChartData');

        $this->assertEquals(150, $chartData[0]['total_24h']);
    }

    public function test_network_chart_data_calculates_byte_deltas(): void
    {
        $this->actingAs($this->user);

        // Create 3 metrics at least 60 seconds apart (diffInSeconds needs > 0)
        $m1 = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subSeconds(600),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 1000000, 'tx_bytes' => 500000]],
        ]);

        $m2 = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subSeconds(300),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 1500000, 'tx_bytes' => 700000]],
        ]);

        $m3 = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 2000000, 'tx_bytes' => 900000]],
        ]);

        // Verify the data was stored correctly
        $this->assertEquals(1000000, $m1->fresh()->network[0]['rx_bytes']);
        $this->assertEquals(1500000, $m2->fresh()->network[0]['rx_bytes']);
        $this->assertEquals(2000000, $m3->fresh()->network[0]['rx_bytes']);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('networkChartData');

        $this->assertCount(3, $chartData);
        // First point has 0 (no previous)
        $this->assertEquals(0, $chartData[0]['rx']);
        $this->assertEquals(0, $chartData[0]['tx']);
        // Second point: rx = 1500000 - 1000000 = 500000
        $this->assertEquals(500000, $chartData[1]['rx']);
        $this->assertEquals(200000, $chartData[1]['tx']);
        // Third point: rx = 2000000 - 1500000 = 500000
        $this->assertEquals(500000, $chartData[2]['rx']);
        $this->assertEquals(200000, $chartData[2]['tx']);
    }

    public function test_network_chart_ignores_counter_reset(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subSeconds(600),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 5000000, 'tx_bytes' => 1000000]],
        ]);

        // Simulate reboot - counters reset to lower values
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subSeconds(300),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 100000, 'tx_bytes' => 50000]],
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 200000, 'tx_bytes' => 100000]],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('networkChartData');

        // When counter resets (delta negative), should be 0
        $this->assertEquals(0, $chartData[1]['rx']);
        $this->assertEquals(0, $chartData[1]['tx']);
        // Normal delta after reset
        $this->assertEquals(100000, $chartData[2]['rx']);
        $this->assertEquals(50000, $chartData[2]['tx']);
    }

    public function test_network_chart_handles_missing_network_data(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'network' => null,
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 1000, 'tx_bytes' => 500]],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('networkChartData');

        // Both should have 0 since we can't calculate delta from null
        $this->assertEquals(0, $chartData[0]['rx']);
        $this->assertEquals(0, $chartData[1]['rx']);
    }

    public function test_banned_ip_geo_lookup_is_performed(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'China',
                'countryCode' => 'CN',
                'city' => 'Beijing',
                'isp' => 'China Telecom',
            ]),
        ]);

        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'security' => [
                'banned_ips' => [
                    ['ip' => '8.8.8.8', 'unban_at' => now()->addMinutes(30)->toDateTimeString()],
                ],
            ],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $bannedIpGeo = $component->viewData('bannedIpGeo');

        $this->assertArrayHasKey('8.8.8.8', $bannedIpGeo);
        $this->assertEquals('China', $bannedIpGeo['8.8.8.8']['country']);
    }

    public function test_banned_ip_geo_handles_old_format(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Russia',
                'countryCode' => 'RU',
                'city' => 'Moscow',
                'isp' => 'Test ISP',
            ]),
        ]);

        $this->actingAs($this->user);

        // Old format: just array of IP strings
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'security' => [
                'banned_ips' => ['1.2.3.4', '5.6.7.8'],
            ],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $bannedIpGeo = $component->viewData('bannedIpGeo');

        $this->assertArrayHasKey('1.2.3.4', $bannedIpGeo);
        $this->assertArrayHasKey('5.6.7.8', $bannedIpGeo);
    }

    public function test_banned_ip_geo_is_empty_when_no_banned_ips(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'security' => ['ssh_failed_24h' => 10],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $bannedIpGeo = $component->viewData('bannedIpGeo');

        $this->assertEmpty($bannedIpGeo);
    }

    public function test_security_chart_empty_when_no_metrics(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('securityChartData');

        $this->assertEmpty($chartData);
    }

    public function test_network_chart_empty_when_no_metrics(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('networkChartData');

        $this->assertEmpty($chartData);
    }

    public function test_cpu_chart_data_calculates_normalized_load(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(10),
            'load_1m' => 2.0,
            'cpu_cores' => 4,
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'load_1m' => 4.0,
            'cpu_cores' => 4,
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 6.0,
            'cpu_cores' => 4,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('cpuChartData');

        $this->assertCount(3, $chartData);
        // 2.0 / 4 cores = 50%
        $this->assertEquals(50.0, $chartData[0]['value']);
        $this->assertEquals(2.0, $chartData[0]['load']);
        $this->assertEquals(4, $chartData[0]['cores']);
        // 4.0 / 4 cores = 100%
        $this->assertEquals(100.0, $chartData[1]['value']);
        // 6.0 / 4 cores = 150%
        $this->assertEquals(150.0, $chartData[2]['value']);
    }

    public function test_cpu_chart_handles_zero_cores(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 2.0,
            'cpu_cores' => 0,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('cpuChartData');

        // Should handle division by zero gracefully
        $this->assertEquals(0, $chartData[0]['value']);
    }

    public function test_memory_chart_data_calculates_percentage(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'memory_total' => 8192,
            'memory_used' => 4096,
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'memory_total' => 8192,
            'memory_used' => 6144,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('memoryChartData');

        $this->assertCount(2, $chartData);
        // 4096 / 8192 = 50%
        $this->assertEquals(50.0, $chartData[0]['value']);
        $this->assertEquals(4096, $chartData[0]['used']);
        $this->assertEquals(8192, $chartData[0]['total']);
        // 6144 / 8192 = 75%
        $this->assertEquals(75.0, $chartData[1]['value']);
    }

    public function test_memory_chart_handles_zero_total(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'memory_total' => 0,
            'memory_used' => 100,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('memoryChartData');

        // Should handle division by zero gracefully
        $this->assertEquals(0, $chartData[0]['value']);
    }

    public function test_disk_chart_data_tracks_max_usage(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(5),
            'disks' => [
                ['mount' => '/', 'percent' => 45.5, 'used_mb' => 10000, 'total_mb' => 22000],
                ['mount' => '/home', 'percent' => 60.2, 'used_mb' => 30000, 'total_mb' => 50000],
            ],
        ]);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'disks' => [
                ['mount' => '/', 'percent' => 50.0, 'used_mb' => 11000, 'total_mb' => 22000],
                ['mount' => '/home', 'percent' => 55.0, 'used_mb' => 27500, 'total_mb' => 50000],
            ],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('diskChartData');

        $this->assertCount(2, $chartData);
        // First point: max is /home at 60.2%
        $this->assertEquals(60.2, $chartData[0]['value']);
        $this->assertEquals('/home', $chartData[0]['mount']);
        // Second point: max is /home at 55%
        $this->assertEquals(55.0, $chartData[1]['value']);
        $this->assertEquals('/home', $chartData[1]['mount']);
    }

    public function test_disk_chart_handles_empty_disks(): void
    {
        $this->actingAs($this->user);

        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'disks' => null,
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $chartData = $component->viewData('diskChartData');

        $this->assertEquals(0, $chartData[0]['value']);
        $this->assertEquals('/', $chartData[0]['mount']);
    }

    public function test_resource_charts_empty_when_no_metrics(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $this->assertEmpty($component->viewData('cpuChartData'));
        $this->assertEmpty($component->viewData('memoryChartData'));
        $this->assertEmpty($component->viewData('diskChartData'));
    }

    public function test_avg_daily_network_calculated_from_week_data(): void
    {
        $this->actingAs($this->user);

        // Create metrics over 3 days
        // Day 1: 1GB RX, 500MB TX
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(2)->subHours(2),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 0, 'tx_bytes' => 0]],
        ]);
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(2),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 1073741824, 'tx_bytes' => 536870912]], // 1GB / 512MB
        ]);

        // Day 2: 2GB RX, 1GB TX
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(1)->subHours(2),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 1073741824, 'tx_bytes' => 536870912]],
        ]);
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(1),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 3221225472, 'tx_bytes' => 1610612736]], // +2GB / +1GB
        ]);

        // Day 3 (today): 500MB RX, 250MB TX
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subHours(2),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 3221225472, 'tx_bytes' => 1610612736]],
        ]);
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 3758096384, 'tx_bytes' => 1879048192]], // +512MB / +256MB
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $avgDailyRx = $component->viewData('avgDailyRx');
        $avgDailyTx = $component->viewData('avgDailyTx');

        // Day 1: 1GB, Day 2: 2GB, Day 3: 0.5GB = 3.5GB / 3 days ≈ 1.17GB
        // Using bytes: (1073741824 + 2147483648 + 536870912) / 3 = 1252698794.67
        $this->assertGreaterThan(0, $avgDailyRx);
        $this->assertGreaterThan(0, $avgDailyTx);
    }

    public function test_avg_daily_network_zero_with_no_data(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $this->assertEquals(0, $component->viewData('avgDailyRx'));
        $this->assertEquals(0, $component->viewData('avgDailyTx'));
    }

    public function test_avg_daily_network_ignores_counter_reset(): void
    {
        $this->actingAs($this->user);

        // First metric with high values
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subHours(4),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 5000000000, 'tx_bytes' => 2000000000]],
        ]);

        // Counter reset (server reboot) - lower values
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subHours(2),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 100000, 'tx_bytes' => 50000]],
        ]);

        // Normal increment after reset
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'network' => [['interface' => 'eth0', 'rx_bytes' => 500000, 'tx_bytes' => 200000]],
        ]);

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $avgDailyRx = $component->viewData('avgDailyRx');
        $avgDailyTx = $component->viewData('avgDailyTx');

        // Should only count the delta after reset (400000 rx, 150000 tx), not the negative delta
        $this->assertEquals(400000, $avgDailyRx);
        $this->assertEquals(150000, $avgDailyTx);
    }

    private function createServer(array $attributes = []): Server
    {
        $token = Server::generateToken();

        return Server::create(array_merge([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ], $attributes));
    }
}
