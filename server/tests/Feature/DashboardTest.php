<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_shows_add_server_button(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Add Server');
    }

    public function test_server_detail_page_requires_authentication(): void
    {
        $user = User::factory()->create();
        $token = Server::generateToken();
        $server = Server::create([
            'user_id' => $user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
        ]);

        $response = $this->get("/servers/{$server->id}");

        $response->assertRedirect('/login');
    }

    public function test_server_detail_page_accessible_by_owner(): void
    {
        $user = User::factory()->create();
        $token = Server::generateToken();
        $server = Server::create([
            'user_id' => $user->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
        ]);

        $response = $this->actingAs($user)->get("/servers/{$server->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Server');
    }

    public function test_server_detail_page_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = Server::generateToken();
        $server = Server::create([
            'user_id' => $owner->id,
            'name' => 'Test Server',
            'token' => $token['hashed'],
            'status' => 'online',
        ]);

        $response = $this->actingAs($otherUser)->get("/servers/{$server->id}");

        $response->assertStatus(403);
    }

    public function test_create_server_page_requires_authentication(): void
    {
        $response = $this->get('/servers/create');

        $response->assertRedirect('/login');
    }

    public function test_create_server_page_accessible_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/servers/create');

        $response->assertStatus(200);
    }
}
