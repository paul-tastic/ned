<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileController extends Controller
{
    /**
     * Dashboard overview: active servers with latest metric and status counts.
     * GET /api/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $servers = Server::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderByRaw("CASE status WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 WHEN 'offline' THEN 3 WHEN 'online' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get()
            ->map(function (Server $server) {
                if ($server->isOffline()) {
                    $server->status = 'offline';
                }

                $metric = $server->latestMetric();

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'hostname' => $server->hostname,
                    'status' => $server->status,
                    'last_seen_at' => $server->last_seen_at?->toIso8601String(),
                    'agent_version' => $server->agent_version,
                    'latest_metric' => $metric ? [
                        'recorded_at' => $metric->recorded_at->toIso8601String(),
                        'uptime' => $metric->uptime,
                        'load_1m' => $metric->load_1m,
                        'load_5m' => $metric->load_5m,
                        'load_15m' => $metric->load_15m,
                        'cpu_cores' => $metric->cpu_cores,
                        'memory_total' => $metric->memory_total,
                        'memory_used' => $metric->memory_used,
                        'memory_percent' => $metric->memory_percent,
                        'swap_total' => $metric->swap_total,
                        'swap_used' => $metric->swap_used,
                        'swap_percent' => $metric->swap_percent,
                        'max_disk_percent' => $metric->max_disk_percent,
                        'disks' => $metric->disks,
                        'normalized_load' => $metric->normalized_load,
                        'failed_services_count' => $metric->failed_services_count,
                    ] : null,
                ];
            });

        $stats = [
            'total' => $servers->count(),
            'online' => $servers->where('status', 'online')->count(),
            'warning' => $servers->where('status', 'warning')->count(),
            'critical' => $servers->where('status', 'critical')->count(),
            'offline' => $servers->where('status', 'offline')->count(),
        ];

        return response()->json([
            'servers' => $servers->values(),
            'stats' => $stats,
        ]);
    }

    /**
     * Server detail with 24 hours of metrics history.
     * GET /api/servers/{server}
     */
    public function show(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($server->isOffline()) {
            $server->status = 'offline';
        }

        $metrics = $server->metrics()
            ->latest('recorded_at')
            ->limit(288)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($metric) {
                return [
                    'id' => $metric->id,
                    'recorded_at' => $metric->recorded_at->toIso8601String(),
                    'uptime' => $metric->uptime,
                    'load_1m' => $metric->load_1m,
                    'load_5m' => $metric->load_5m,
                    'load_15m' => $metric->load_15m,
                    'cpu_cores' => $metric->cpu_cores,
                    'memory_total' => $metric->memory_total,
                    'memory_used' => $metric->memory_used,
                    'memory_percent' => $metric->memory_percent,
                    'swap_total' => $metric->swap_total,
                    'swap_used' => $metric->swap_used,
                    'swap_percent' => $metric->swap_percent,
                    'max_disk_percent' => $metric->max_disk_percent,
                    'disks' => $metric->disks,
                    'network' => $metric->network,
                    'services' => $metric->services,
                    'security' => $metric->security,
                    'normalized_load' => $metric->normalized_load,
                    'failed_services_count' => $metric->failed_services_count,
                ];
            });

        $latestMetric = $metrics->last();

        return response()->json([
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'hostname' => $server->hostname,
                'status' => $server->status,
                'last_seen_at' => $server->last_seen_at?->toIso8601String(),
                'agent_version' => $server->agent_version,
            ],
            'latest_metric' => $latestMetric,
            'metrics' => $metrics,
        ]);
    }
}
