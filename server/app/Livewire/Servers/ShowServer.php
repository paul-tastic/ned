<?php

namespace App\Livewire\Servers;

use App\Models\BannedIpEvent;
use App\Models\Server;
use App\Services\GeoIpService;
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

        // Build network chart data (bytes transferred per interval)
        $networkChartData = [];
        $prevNetwork = null;
        $prevRecordedAt = null;
        foreach ($metrics as $metric) {
            $network = $metric->network[0] ?? null; // Primary interface (eth0)
            $rxBytes = 0;
            $txBytes = 0;

            if ($prevNetwork !== null && $network !== null && $prevRecordedAt !== null) {
                $seconds = abs($metric->recorded_at->diffInSeconds($prevRecordedAt));
                if ($seconds > 0) {
                    $rxDelta = ($network['rx_bytes'] ?? 0) - ($prevNetwork['rx_bytes'] ?? 0);
                    $txDelta = ($network['tx_bytes'] ?? 0) - ($prevNetwork['tx_bytes'] ?? 0);
                    // Only use if positive (ignore counter resets)
                    if ($rxDelta >= 0 && $txDelta >= 0) {
                        $rxBytes = $rxDelta;
                        $txBytes = $txDelta;
                    }
                }
            }

            $networkChartData[] = [
                'time' => $metric->recorded_at->format('H:i') . ' UTC',
                'rx' => $rxBytes,
                'tx' => $txBytes,
            ];

            $prevNetwork = $network;
            $prevRecordedAt = $metric->recorded_at;
        }

        // Build CPU chart data (normalized load over time)
        $cpuChartData = [];
        foreach ($metrics as $metric) {
            $cpuCores = $metric->cpu_cores ?? 1;
            $load = $metric->load_1m ?? 0;
            $normalizedLoad = $cpuCores > 0 ? round(($load / $cpuCores) * 100, 1) : 0;

            $cpuChartData[] = [
                'time' => $metric->recorded_at->format('H:i') . ' UTC',
                'value' => $normalizedLoad,
                'load' => $load,
                'cores' => $cpuCores,
            ];
        }

        // Build memory chart data (usage percentage over time)
        $memoryChartData = [];
        foreach ($metrics as $metric) {
            $memoryTotal = $metric->memory_total ?? 0;
            $memoryUsed = $metric->memory_used ?? 0;
            $memoryPercent = $memoryTotal > 0 ? round(($memoryUsed / $memoryTotal) * 100, 1) : 0;

            $memoryChartData[] = [
                'time' => $metric->recorded_at->format('H:i') . ' UTC',
                'value' => $memoryPercent,
                'used' => $memoryUsed,
                'total' => $memoryTotal,
            ];
        }

        // Build disk chart data (max disk usage percentage over time)
        $diskChartData = [];
        foreach ($metrics as $metric) {
            $maxPercent = 0;
            $maxMount = '/';
            if (!empty($metric->disks)) {
                foreach ($metric->disks as $disk) {
                    $percent = $disk['percent'] ?? 0;
                    if ($percent > $maxPercent) {
                        $maxPercent = $percent;
                        $maxMount = $disk['mount'] ?? '/';
                    }
                }
            }

            $diskChartData[] = [
                'time' => $metric->recorded_at->format('H:i') . ' UTC',
                'value' => round($maxPercent, 1),
                'mount' => $maxMount,
            ];
        }

        // Get geo data for banned IPs (server-side lookup)
        $bannedIpGeo = [];
        $bannedIpCounts = [];
        if ($latestMetric && !empty($latestMetric->security['banned_ips'])) {
            $geoService = app(GeoIpService::class);
            // Handle both old format (array of strings) and new format (array of objects with ip/unban_at)
            $ips = collect($latestMetric->security['banned_ips'])->map(function ($item) {
                return is_array($item) ? ($item['ip'] ?? null) : $item;
            })->filter()->values()->toArray();
            $bannedIpGeo = $geoService->lookupMany($ips);
            $bannedIpCounts = BannedIpEvent::getBanCounts($this->server->id, $ips);
        }

        return view('livewire.servers.show-server', [
            'metrics' => $metrics,
            'latestMetric' => $latestMetric,
            'previousMetric' => $previousMetric,
            'securityChartData' => $securityChartData,
            'networkChartData' => $networkChartData,
            'cpuChartData' => $cpuChartData,
            'memoryChartData' => $memoryChartData,
            'diskChartData' => $diskChartData,
            'bannedIpGeo' => $bannedIpGeo,
            'bannedIpCounts' => $bannedIpCounts,
        ]);
    }
}
