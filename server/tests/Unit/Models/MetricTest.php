<?php

namespace Tests\Unit\Models;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricTest extends TestCase
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

    public function test_memory_percent_is_calculated_correctly(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'memory_total' => 8192,
            'memory_used' => 4096,
        ]);

        $this->assertEquals(50.0, $metric->memory_percent);
    }

    public function test_memory_percent_returns_null_when_total_is_zero(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'memory_total' => 0,
            'memory_used' => 0,
        ]);

        $this->assertNull($metric->memory_percent);
    }

    public function test_memory_percent_returns_null_when_total_is_null(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
        ]);

        $this->assertNull($metric->memory_percent);
    }

    public function test_swap_percent_is_calculated_correctly(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'swap_total' => 2048,
            'swap_used' => 512,
        ]);

        $this->assertEquals(25.0, $metric->swap_percent);
    }

    public function test_swap_percent_returns_null_when_no_swap(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'swap_total' => 0,
            'swap_used' => 0,
        ]);

        $this->assertNull($metric->swap_percent);
    }

    public function test_normalized_load_is_calculated_correctly(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 2.0,
            'cpu_cores' => 4,
        ]);

        $this->assertEquals(0.5, $metric->normalized_load);
    }

    public function test_normalized_load_returns_null_when_no_cores(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 2.0,
            'cpu_cores' => 0,
        ]);

        $this->assertNull($metric->normalized_load);
    }

    public function test_max_disk_percent_returns_highest_value(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'disks' => [
                ['mount' => '/', 'percent' => 50],
                ['mount' => '/home', 'percent' => 75],
                ['mount' => '/var', 'percent' => 60],
            ],
        ]);

        $this->assertEquals(75.0, $metric->max_disk_percent);
    }

    public function test_max_disk_percent_returns_null_when_no_disks(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'disks' => [],
        ]);

        $this->assertNull($metric->max_disk_percent);
    }

    public function test_failed_services_count_with_array_format(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'services' => [
                ['name' => 'nginx', 'status' => 'running'],
                ['name' => 'mysql', 'status' => 'stopped'],
                ['name' => 'php-fpm', 'status' => 'running'],
                ['name' => 'redis', 'status' => 'stopped'],
            ],
        ]);

        $this->assertEquals(2, $metric->failed_services_count);
    }

    public function test_failed_services_count_returns_zero_when_all_running(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'services' => [
                ['name' => 'nginx', 'status' => 'running'],
                ['name' => 'mysql', 'status' => 'running'],
            ],
        ]);

        $this->assertEquals(0, $metric->failed_services_count);
    }

    public function test_failed_services_count_returns_zero_when_no_services(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'services' => [],
        ]);

        $this->assertEquals(0, $metric->failed_services_count);
    }

    public function test_from_agent_payload_creates_metric_correctly(): void
    {
        $payload = [
            'system' => [
                'uptime' => 86400,
                'load' => ['1m' => 0.5, '5m' => 0.7, '15m' => 0.6],
                'cpu_cores' => 4,
            ],
            'memory' => [
                'mem' => ['total' => 8192, 'used' => 4096, 'available' => 4096],
                'swap' => ['total' => 2048, 'used' => 128],
            ],
            'disks' => [
                ['mount' => '/', 'total_mb' => 50000, 'used_mb' => 25000, 'percent' => 50],
            ],
            'services' => [
                ['name' => 'nginx', 'status' => 'running'],
            ],
            'network' => [
                ['interface' => 'eth0', 'rx_bytes' => 100, 'tx_bytes' => 200],
            ],
            'security' => [
                'ssh_failed_24h' => 10,
            ],
        ];

        $metric = Metric::fromAgentPayload($this->server, $payload);

        $this->assertEquals($this->server->id, $metric->server_id);
        $this->assertEquals(86400, $metric->uptime);
        $this->assertEquals(0.5, $metric->load_1m);
        $this->assertEquals(0.7, $metric->load_5m);
        $this->assertEquals(0.6, $metric->load_15m);
        $this->assertEquals(4, $metric->cpu_cores);
        $this->assertEquals(8192, $metric->memory_total);
        $this->assertEquals(4096, $metric->memory_used);
        $this->assertEquals(4096, $metric->memory_available);
        $this->assertEquals(2048, $metric->swap_total);
        $this->assertEquals(128, $metric->swap_used);
        $this->assertIsArray($metric->disks);
        $this->assertIsArray($metric->services);
        $this->assertIsArray($metric->network);
        $this->assertIsArray($metric->security);
    }

    public function test_from_agent_payload_handles_missing_data_gracefully(): void
    {
        $payload = [];

        $metric = Metric::fromAgentPayload($this->server, $payload);

        $this->assertEquals($this->server->id, $metric->server_id);
        $this->assertNull($metric->uptime);
        $this->assertNull($metric->load_1m);
        $this->assertNull($metric->memory_total);
    }

    public function test_metric_belongs_to_server(): void
    {
        $metric = Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
        ]);

        $this->assertInstanceOf(Server::class, $metric->server);
        $this->assertEquals($this->server->id, $metric->server->id);
    }
}
