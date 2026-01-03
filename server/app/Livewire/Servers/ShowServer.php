<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShowServer extends Component
{
    public Server $server;

    public bool $showDeleteModal = false;

    public ?string $newToken = null;

    public function mount(Server $server)
    {
        // Authorize access
        if ($server->user_id !== Auth::id()) {
            abort(403);
        }

        $this->server = $server;
    }

    public function regenerateToken()
    {
        $token = Server::generateToken();
        $this->server->update(['token' => $token['hashed']]);
        $this->newToken = $token['plain'];
    }

    public function delete()
    {
        $this->server->delete();

        return redirect()->route('dashboard')->with('message', 'Server deleted.');
    }

    public function render()
    {
        $metrics = $this->server->metrics()
            ->latest('recorded_at')
            ->limit(60)
            ->get()
            ->reverse();

        $latestMetric = $metrics->last();

        return view('livewire.servers.show-server', [
            'metrics' => $metrics,
            'latestMetric' => $latestMetric,
        ]);
    }
}
