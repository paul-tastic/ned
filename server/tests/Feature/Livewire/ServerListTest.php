<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard\ServerList;
use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServerListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ServerList::class)
            ->assertStatus(200);
    }

    public function test_shows_user_servers(): void
    {
        $this->actingAs($this->user);

        $server = $this->createServer(['name' => 'My Test Server']);

        Livewire::test(ServerList::class)
            ->assertSee('My Test Server');
    }

    public function test_does_not_show_other_users_servers(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $this->createServer(['name' => 'Other User Server', 'user_id' => $otherUser->id]);
        $this->createServer(['name' => 'My Server']);

        Livewire::test(ServerList::class)
            ->assertSee('My Server')
            ->assertDontSee('Other User Server');
    }

    public function test_calculates_stats_correctly(): void
    {
        $this->actingAs($this->user);

        $this->createServer(['status' => 'online']);
        $this->createServer(['status' => 'online']);
        $this->createServer(['status' => 'warning']);
        $this->createServer(['status' => 'critical']);
        $this->createServer(['status' => 'offline']);

        $component = Livewire::test(ServerList::class);

        $stats = $component->viewData('stats');

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['online']);
        $this->assertEquals(1, $stats['warning']);
        $this->assertEquals(1, $stats['critical']);
        $this->assertEquals(1, $stats['offline']);
    }

    public function test_orders_servers_by_status_priority(): void
    {
        $this->actingAs($this->user);

        // Create in random order
        $online = $this->createServer(['name' => 'Online Server', 'status' => 'online']);
        $critical = $this->createServer(['name' => 'Critical Server', 'status' => 'critical']);
        $warning = $this->createServer(['name' => 'Warning Server', 'status' => 'warning']);
        $offline = $this->createServer(['name' => 'Offline Server', 'status' => 'offline']);

        $component = Livewire::test(ServerList::class);

        $servers = $component->viewData('servers');

        // Critical should be first, then warning, offline, online
        $this->assertEquals('Critical Server', $servers[0]->name);
        $this->assertEquals('Warning Server', $servers[1]->name);
        $this->assertEquals('Offline Server', $servers[2]->name);
        $this->assertEquals('Online Server', $servers[3]->name);
    }

    public function test_includes_latest_metric_for_each_server(): void
    {
        $this->actingAs($this->user);

        $server = $this->createServer();

        Metric::create([
            'server_id' => $server->id,
            'recorded_at' => now()->subHour(),
            'load_1m' => 1.0,
        ]);

        Metric::create([
            'server_id' => $server->id,
            'recorded_at' => now(),
            'load_1m' => 2.0,
        ]);

        $component = Livewire::test(ServerList::class);

        $servers = $component->viewData('servers');

        $this->assertNotNull($servers[0]->latest_metric);
        $this->assertEquals(2.0, $servers[0]->latest_metric->load_1m);
    }

    public function test_empty_state_when_no_servers(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ServerList::class);

        $stats = $component->viewData('stats');
        $servers = $component->viewData('servers');

        $this->assertEquals(0, $stats['total']);
        $this->assertCount(0, $servers);
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
