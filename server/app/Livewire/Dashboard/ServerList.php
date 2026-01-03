<?php

namespace App\Livewire\Dashboard;

use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ServerList extends Component
{
    public function render()
    {
        $servers = Server::where('user_id', Auth::id())
            ->orderByRaw("FIELD(status, 'critical', 'warning', 'offline', 'online')")
            ->orderBy('name')
            ->get()
            ->map(function ($server) {
                $server->latest_metric = $server->latestMetric();

                return $server;
            });

        $stats = [
            'total' => $servers->count(),
            'online' => $servers->where('status', 'online')->count(),
            'warning' => $servers->where('status', 'warning')->count(),
            'critical' => $servers->where('status', 'critical')->count(),
            'offline' => $servers->where('status', 'offline')->count(),
        ];

        return view('livewire.dashboard.server-list', [
            'servers' => $servers,
            'stats' => $stats,
        ]);
    }
}
