<?php

namespace Tests\Feature\Commands;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneMetricsTest extends TestCase
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

    public function test_prunes_metrics_older_than_one_year(): void
    {
        // Old metric (2 years ago)
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(730),
            'load_1m' => 0.5,
        ]);

        // Recent metric (30 days ago)
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(30),
            'load_1m' => 0.6,
        ]);

        $this->artisan('ned:prune-metrics')
            ->expectsOutputToContain('Pruning 1 metrics')
            ->assertSuccessful();

        $this->assertDatabaseCount('metrics', 1);
    }

    public function test_respects_custom_days_option(): void
    {
        // 100 days old
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(100),
            'load_1m' => 0.5,
        ]);

        // 50 days old
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subDays(50),
            'load_1m' => 0.6,
        ]);

        $this->artisan('ned:prune-metrics', ['--days' => 60])
            ->assertSuccessful();

        $this->assertDatabaseCount('metrics', 1);
    }

    public function test_handles_no_metrics_to_prune(): void
    {
        // Recent metric only
        Metric::create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
            'load_1m' => 0.5,
        ]);

        $this->artisan('ned:prune-metrics')
            ->expectsOutputToContain('No old metrics to prune')
            ->assertSuccessful();

        $this->assertDatabaseCount('metrics', 1);
    }

    public function test_handles_empty_table(): void
    {
        $this->artisan('ned:prune-metrics')
            ->expectsOutputToContain('No old metrics to prune')
            ->assertSuccessful();
    }
}
