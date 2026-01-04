<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShowServer extends Component
{
    public Server $server;

    public bool $showDeleteModal = false;

    public function mount(Server $server)
    {
        // Authorize access
        if ($server->user_id !== Auth::id()) {
            abort(403);
        }

        $this->server = $server;
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
            ->limit(288) // 24 hours at 5-min intervals
            ->get()
            ->reverse();

        $metricsArray = $metrics->values();
        $latestMetric = $metricsArray->last();
        $previousMetric = $metricsArray->count() > 1 ? $metricsArray[$metricsArray->count() - 2] : null;

        // Build security chart data (SSH failed attempts delta per interval)
        $securityChartData = [];
        $prevSshFailed = null;
        foreach ($metrics as $metric) {
            $sshFailed = $metric->security['ssh_failed_24h'] ?? 0;
            $delta = 0;
            if ($prevSshFailed !== null && $sshFailed >= $prevSshFailed) {
                $delta = $sshFailed - $prevSshFailed;
            }
            $securityChartData[] = [
                'time' => $metric->recorded_at->format('H:i') . ' UTC',
                'timestamp' => $metric->recorded_at->toIso8601String(),
                'attacks' => $delta,
                'total_24h' => $sshFailed,
            ];
            $prevSshFailed = $sshFailed;
        }

        return view('livewire.servers.show-server', [
            'metrics' => $metrics,
            'latestMetric' => $latestMetric,
            'previousMetric' => $previousMetric,
            'securityChartData' => $securityChartData,
        ]);
    }
}
