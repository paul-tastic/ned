<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateServer extends Component
{
    public string $name = '';

    public string $hostname = '';

    public ?string $plainToken = null;

    public ?Server $server = null;

    public function create()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
        ]);

        $token = Server::generateToken();

        $this->server = Server::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'hostname' => $this->hostname ?: null,
            'token' => $token['hashed'],
            'status' => 'offline',
        ]);

        // Show the plain token once
        $this->plainToken = $token['plain'];
    }

    public function render()
    {
        return view('livewire.servers.create-server');
    }
}
