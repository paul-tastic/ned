<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\CreateServer;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateServerTest extends TestCase
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

        Livewire::test(CreateServer::class)
            ->assertStatus(200);
    }

    public function test_can_create_server_with_name_only(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', 'My New Server')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('servers', [
            'user_id' => $this->user->id,
            'name' => 'My New Server',
            'hostname' => null,
            'status' => 'offline',
        ]);
    }

    public function test_can_create_server_with_hostname(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', 'Production Server')
            ->set('hostname', 'prod.example.com')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('servers', [
            'user_id' => $this->user->id,
            'name' => 'Production Server',
            'hostname' => 'prod.example.com',
        ]);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', '')
            ->call('create')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_name_max_length(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', str_repeat('a', 256))
            ->call('create')
            ->assertHasErrors(['name' => 'max']);
    }

    public function test_hostname_max_length(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', 'Valid Name')
            ->set('hostname', str_repeat('a', 256))
            ->call('create')
            ->assertHasErrors(['hostname' => 'max']);
    }

    public function test_returns_plain_token_after_creation(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(CreateServer::class)
            ->set('name', 'Token Test Server')
            ->call('create');

        $this->assertNotNull($component->get('plainToken'));
        $this->assertEquals(64, strlen($component->get('plainToken')));
    }

    public function test_server_is_assigned_to_current_user(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', 'User Server')
            ->call('create');

        $server = Server::where('name', 'User Server')->first();

        $this->assertEquals($this->user->id, $server->user_id);
    }

    public function test_server_starts_with_offline_status(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', 'Status Test')
            ->call('create');

        $server = Server::where('name', 'Status Test')->first();

        $this->assertEquals('offline', $server->status);
    }

    public function test_server_has_hashed_token(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(CreateServer::class)
            ->set('name', 'Hash Test')
            ->call('create');

        $plainToken = $component->get('plainToken');
        $server = Server::where('name', 'Hash Test')->first();

        // Verify the stored token is the hash of the plain token
        $this->assertEquals(hash('sha256', $plainToken), $server->token);
    }

    public function test_empty_hostname_is_stored_as_null(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateServer::class)
            ->set('name', 'Null Hostname Test')
            ->set('hostname', '')
            ->call('create');

        $server = Server::where('name', 'Null Hostname Test')->first();

        $this->assertNull($server->hostname);
    }
}
