<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ShowServer;
use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_metrics_limited_to_60(): void
    {
        $this->actingAs($this->user);

        // Create 70 metrics
        for ($i = 0; $i < 70; $i++) {
            Metric::create([
                'server_id' => $this->server->id,
                'recorded_at' => now()->subMinutes(70 - $i),
                'load_1m' => $i,
            ]);
        }

        $component = Livewire::test(ShowServer::class, ['server' => $this->server]);

        $metrics = $component->viewData('metrics');

        $this->assertCount(60, $metrics);
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
