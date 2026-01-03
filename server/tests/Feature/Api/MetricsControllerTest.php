<?php

namespace Tests\Feature\Api;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;
    private string $plainToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $token = Server::generateToken();
        $this->plainToken = $token['plain'];

        $this->server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'hostname' => 'test.example.com',
            'token' => $token['hashed'],
            'status' => 'online',
            'is_active' => true,
        ]);
    }

    public function test_metrics_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/metrics', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_metrics_endpoint_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/metrics', $this->validPayload(), [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_metrics_endpoint_accepts_valid_token(): void
    {
        $response = $this->postJson('/api/metrics', $this->validPayload(), [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['success', 'metric_id']);
    }

    public function test_metrics_are_stored_in_database(): void
    {
        $this->postJson('/api/metrics', $this->validPayload(), [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $this->assertDatabaseCount('metrics', 1);

        $metric = Metric::first();
        $this->assertEquals($this->server->id, $metric->server_id);
        $this->assertEquals(0.5, $metric->load_1m);
        $this->assertEquals(8192, $metric->memory_total);
    }

    public function test_server_last_seen_is_updated(): void
    {
        $this->assertNull($this->server->fresh()->last_seen_at);

        $this->postJson('/api/metrics', $this->validPayload(), [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $this->assertNotNull($this->server->fresh()->last_seen_at);
    }

    public function test_agent_version_is_stored_on_server(): void
    {
        $this->assertNull($this->server->fresh()->agent_version);

        $payload = $this->validPayload();
        $payload['agent_version'] = '0.2.0';

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $this->assertEquals('0.2.0', $this->server->fresh()->agent_version);
    }

    public function test_server_status_becomes_warning_on_high_memory(): void
    {
        $payload = $this->validPayload();
        $payload['memory']['mem']['used'] = 7000;  // ~85% of 8192

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $this->assertEquals('warning', $this->server->fresh()->status);
    }

    public function test_server_status_becomes_critical_on_very_high_memory(): void
    {
        $payload = $this->validPayload();
        $payload['memory']['mem']['used'] = 7900;  // ~96% of 8192

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $this->assertEquals('critical', $this->server->fresh()->status);
    }

    public function test_server_status_becomes_warning_on_high_disk(): void
    {
        $payload = $this->validPayload();
        $payload['disks'] = [
            ['mount' => '/', 'total_mb' => 100000, 'used_mb' => 85000, 'percent' => 85],
        ];

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $this->assertEquals('warning', $this->server->fresh()->status);
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
        $response->assertJsonStructure(['status', 'timestamp']);
    }

    public function test_services_are_stored_as_json(): void
    {
        $payload = $this->validPayload();
        $payload['services'] = [
            ['name' => 'nginx', 'status' => 'running'],
            ['name' => 'mysql', 'status' => 'running'],
        ];

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $metric = Metric::first();
        $this->assertIsArray($metric->services);
        $this->assertCount(2, $metric->services);
        $this->assertEquals('nginx', $metric->services[0]['name']);
    }

    public function test_network_data_is_stored(): void
    {
        $payload = $this->validPayload();
        $payload['network'] = [
            ['interface' => 'eth0', 'rx_bytes' => 123456789, 'tx_bytes' => 987654321],
        ];

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $metric = Metric::first();
        $this->assertIsArray($metric->network);
        $this->assertEquals('eth0', $metric->network[0]['interface']);
    }

    public function test_security_data_is_stored(): void
    {
        $payload = $this->validPayload();
        $payload['security'] = [
            'ssh_failed_24h' => 150,
            'f2b_currently_banned' => 3,
            'f2b_total_banned' => 47,
        ];

        $this->postJson('/api/metrics', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        $metric = Metric::first();
        $this->assertIsArray($metric->security);
        $this->assertEquals(150, $metric->security['ssh_failed_24h']);
    }

    private function validPayload(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'hostname' => 'test.example.com',
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
        ];
    }
}
