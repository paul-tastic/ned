<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Metric;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    /**
     * Receive metrics from an agent.
     * POST /api/metrics
     */
    public function store(Request $request): JsonResponse
    {
        // Server is attached by the AuthenticateServer middleware
        $server = $request->attributes->get('server');

        if (! $server) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Create metric from agent payload
        $metric = Metric::fromAgentPayload($server, $request->all());

        // Update server info and status
        $server->markAsSeen();

        // Update agent version if provided
        if ($agentVersion = $request->input('agent_version')) {
            $server->update(['agent_version' => $agentVersion]);
        }

        $this->updateServerStatus($server, $metric);

        return response()->json([
            'success' => true,
            'metric_id' => $metric->id,
        ], 201);
    }

    /**
     * Update server status based on latest metrics.
     */
    protected function updateServerStatus(Server $server, Metric $metric): void
    {
        $status = 'online';

        // Check for critical conditions
        if ($metric->memory_percent >= 95 || $metric->max_disk_percent >= 95) {
            $status = 'critical';
        } elseif ($metric->memory_percent >= 80 || $metric->max_disk_percent >= 80 || $metric->normalized_load >= 1.5) {
            $status = 'warning';
        }

        // Check for failed services
        if ($metric->failed_services_count > 0) {
            $status = 'warning';
        }

        $server->updateStatus($status);
    }

    /**
     * Health check endpoint.
     * GET /api/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
