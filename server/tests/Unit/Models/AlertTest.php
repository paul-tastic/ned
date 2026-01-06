<?php

namespace Tests\Unit\Models;

use App\Models\Alert;
use App\Models\Server;
use App\Models\Threshold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
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
        ]);
    }

    public function test_alert_belongs_to_server(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $this->assertEquals($this->server->id, $alert->server->id);
    }

    public function test_acknowledge_sets_timestamp(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $this->assertNull($alert->acknowledged_at);

        $alert->acknowledge();

        $this->assertNotNull($alert->fresh()->acknowledged_at);
    }

    public function test_resolve_sets_timestamp(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $this->assertNull($alert->resolved_at);

        $alert->resolve();

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_mark_notified_sets_timestamp(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $this->assertNull($alert->notified_at);

        $alert->markNotified();

        $this->assertNotNull($alert->fresh()->notified_at);
    }

    public function test_is_active_returns_true_when_not_resolved(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $this->assertTrue($alert->isActive());
    }

    public function test_is_active_returns_false_when_resolved(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
            'resolved_at' => now(),
        ]);

        $this->assertFalse($alert->isActive());
    }

    public function test_is_acknowledged_returns_correct_value(): void
    {
        $alert = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $this->assertFalse($alert->isAcknowledged());

        $alert->acknowledge();

        $this->assertTrue($alert->fresh()->isAcknowledged());
    }

    public function test_is_critical_returns_correct_value(): void
    {
        $warning = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'CPU high',
        ]);

        $critical = Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'critical',
            'value' => 95.0,
            'threshold' => 90.0,
            'message' => 'CPU critical',
        ]);

        $this->assertFalse($warning->isCritical());
        $this->assertTrue($critical->isCritical());
    }

    public function test_scope_active_filters_unresolved(): void
    {
        Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'Active alert',
        ]);

        Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'memory_percent',
            'level' => 'warning',
            'value' => 85.0,
            'threshold' => 80.0,
            'message' => 'Resolved alert',
            'resolved_at' => now(),
        ]);

        $active = Alert::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('Active alert', $active->first()->message);
    }

    public function test_scope_unacknowledged_filters_correctly(): void
    {
        Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'Unacknowledged',
        ]);

        Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'memory_percent',
            'level' => 'warning',
            'value' => 85.0,
            'threshold' => 80.0,
            'message' => 'Acknowledged',
            'acknowledged_at' => now(),
        ]);

        $unacked = Alert::unacknowledged()->get();

        $this->assertCount(1, $unacked);
        $this->assertEquals('Unacknowledged', $unacked->first()->message);
    }

    public function test_scope_warning_filters_level(): void
    {
        Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 75.5,
            'threshold' => 70.0,
            'message' => 'Warning',
        ]);

        Alert::create([
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'level' => 'critical',
            'value' => 95.0,
            'threshold' => 90.0,
            'message' => 'Critical',
        ]);

        $this->assertCount(1, Alert::warning()->get());
        $this->assertCount(1, Alert::critical()->get());
    }

    public function test_create_from_threshold(): void
    {
        $user = $this->server->user;

        $threshold = Threshold::create([
            'user_id' => $user->id,
            'server_id' => null,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $alert = Alert::createFromThreshold($this->server, $threshold, 85.5, 'warning');

        $this->assertEquals($this->server->id, $alert->server_id);
        $this->assertEquals('cpu_load', $alert->metric);
        $this->assertEquals('warning', $alert->level);
        $this->assertEquals(85.5, $alert->value);
        $this->assertEquals(70.0, $alert->threshold);
        $this->assertStringContainsString('85.5', $alert->message);
    }

    public function test_create_from_threshold_critical(): void
    {
        $user = $this->server->user;

        $threshold = Threshold::create([
            'user_id' => $user->id,
            'server_id' => null,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $alert = Alert::createFromThreshold($this->server, $threshold, 95.0, 'critical');

        $this->assertEquals('critical', $alert->level);
        $this->assertEquals(90.0, $alert->threshold);
    }
}
