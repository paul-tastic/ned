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
            ->orderByRaw("CASE status WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 WHEN 'offline' THEN 3 WHEN 'online' THEN 4 ELSE 5 END")
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
