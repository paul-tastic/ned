<?php

namespace Tests\Unit\Models;

use App\Models\Server;
use App\Models\Threshold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThresholdTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $token = Server::generateToken();
        $this->server = Server::create([
            'user_id' => $this->user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
        ]);
    }

    public function test_threshold_belongs_to_user(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $this->assertEquals($this->user->id, $threshold->user->id);
    }

    public function test_threshold_belongs_to_server(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $this->assertEquals($this->server->id, $threshold->server->id);
    }

    public function test_check_returns_null_when_inactive(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => false,
        ]);

        $this->assertNull($threshold->check(95.0));
    }

    public function test_check_returns_null_when_below_warning(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $this->assertNull($threshold->check(50.0));
    }

    public function test_check_returns_warning_when_above_warning(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $this->assertEquals('warning', $threshold->check(75.0));
    }

    public function test_check_returns_critical_when_above_critical(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $this->assertEquals('critical', $threshold->check(95.0));
    }

    public function test_check_with_less_than_comparison(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'disk_free',
            'warning_value' => 20.0,
            'critical_value' => 10.0,
            'comparison' => '<',
            'is_active' => true,
        ]);

        $this->assertNull($threshold->check(50.0)); // Above warning
        $this->assertEquals('warning', $threshold->check(15.0)); // Below warning
        $this->assertEquals('critical', $threshold->check(5.0)); // Below critical
    }

    public function test_check_with_greater_than_equal_comparison(): void
    {
        $threshold = Threshold::create([
            'user_id' => $this->user->id,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>=',
            'is_active' => true,
        ]);

        $this->assertNull($threshold->check(69.9));
        $this->assertEquals('warning', $threshold->check(70.0)); // Exact match
        $this->assertEquals('critical', $threshold->check(90.0)); // Exact match
    }

    public function test_get_for_server_metric_returns_server_specific(): void
    {
        // Global threshold
        Threshold::create([
            'user_id' => $this->user->id,
            'server_id' => null,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        // Server-specific threshold
        $serverThreshold = Threshold::create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'metric' => 'cpu_load',
            'warning_value' => 80.0,
            'critical_value' => 95.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $result = Threshold::getForServerMetric($this->user->id, $this->server->id, 'cpu_load');

        $this->assertEquals($serverThreshold->id, $result->id);
        $this->assertEquals(80.0, $result->warning_value);
    }

    public function test_get_for_server_metric_falls_back_to_global(): void
    {
        // Only global threshold
        $globalThreshold = Threshold::create([
            'user_id' => $this->user->id,
            'server_id' => null,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => true,
        ]);

        $result = Threshold::getForServerMetric($this->user->id, $this->server->id, 'cpu_load');

        $this->assertEquals($globalThreshold->id, $result->id);
    }

    public function test_get_for_server_metric_returns_null_when_none(): void
    {
        $result = Threshold::getForServerMetric($this->user->id, $this->server->id, 'cpu_load');

        $this->assertNull($result);
    }

    public function test_get_for_server_metric_ignores_inactive(): void
    {
        Threshold::create([
            'user_id' => $this->user->id,
            'server_id' => null,
            'metric' => 'cpu_load',
            'warning_value' => 70.0,
            'critical_value' => 90.0,
            'comparison' => '>',
            'is_active' => false,
        ]);

        $result = Threshold::getForServerMetric($this->user->id, $this->server->id, 'cpu_load');

        $this->assertNull($result);
    }

    public function test_create_defaults_for_user(): void
    {
        Threshold::createDefaultsForUser($this->user);

        $thresholds = Threshold::where('user_id', $this->user->id)->get();

        $this->assertCount(count(Threshold::DEFAULTS), $thresholds);

        // Verify cpu_load default
        $cpu = $thresholds->firstWhere('metric', 'cpu_load');
        $this->assertEquals(70, $cpu->warning_value);
        $this->assertEquals(90, $cpu->critical_value);
        $this->assertEquals('>', $cpu->comparison);
        $this->assertTrue($cpu->is_active);

        // Verify memory_percent default
        $memory = $thresholds->firstWhere('metric', 'memory_percent');
        $this->assertEquals(80, $memory->warning_value);
        $this->assertEquals(95, $memory->critical_value);
    }

    public function test_defaults_constant_has_expected_metrics(): void
    {
        $this->assertArrayHasKey('cpu_load', Threshold::DEFAULTS);
        $this->assertArrayHasKey('memory_percent', Threshold::DEFAULTS);
        $this->assertArrayHasKey('disk_percent', Threshold::DEFAULTS);
        $this->assertArrayHasKey('swap_percent', Threshold::DEFAULTS);
    }
}
