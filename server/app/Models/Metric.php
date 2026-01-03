<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'recorded_at',
        'uptime',
        'load_1m',
        'load_5m',
        'load_15m',
        'cpu_cores',
        'memory_total',
        'memory_used',
        'memory_available',
        'swap_total',
        'swap_used',
        'disks',
        'services',
        'security',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'load_1m' => 'decimal:2',
        'load_5m' => 'decimal:2',
        'load_15m' => 'decimal:2',
        'disks' => 'array',
        'services' => 'array',
        'security' => 'array',
    ];

    // Relationships

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    // Computed attributes

    /**
     * Get memory usage as a percentage.
     */
    public function getMemoryPercentAttribute(): ?float
    {
        if ($this->memory_total === null || $this->memory_total === 0) {
            return null;
        }

        return round(($this->memory_used / $this->memory_total) * 100, 2);
    }

    /**
     * Get swap usage as a percentage.
     */
    public function getSwapPercentAttribute(): ?float
    {
        if ($this->swap_total === null || $this->swap_total === 0) {
            return null;
        }

        return round(($this->swap_used / $this->swap_total) * 100, 2);
    }

    /**
     * Get normalized CPU load (load / cores).
     */
    public function getNormalizedLoadAttribute(): ?float
    {
        if ($this->cpu_cores === null || $this->cpu_cores === 0) {
            return null;
        }

        return round($this->load_1m / $this->cpu_cores, 2);
    }

    /**
     * Get the highest disk usage percentage.
     */
    public function getMaxDiskPercentAttribute(): ?float
    {
        if (empty($this->disks)) {
            return null;
        }

        $maxPercent = 0;
        foreach ($this->disks as $disk) {
            if (isset($disk['percent']) && $disk['percent'] > $maxPercent) {
                $maxPercent = $disk['percent'];
            }
        }

        return $maxPercent;
    }

    /**
     * Get count of failed services.
     */
    public function getFailedServicesCountAttribute(): int
    {
        if (empty($this->services)) {
            return 0;
        }

        $failed = 0;
        foreach ($this->services as $service) {
            // Handle both array format [{name, status}] and key-value format {name: status}
            $status = is_array($service) ? ($service['status'] ?? 'unknown') : $service;
            if ($status !== 'running') {
                $failed++;
            }
        }

        return $failed;
    }

    /**
     * Create a metric from agent payload.
     */
    public static function fromAgentPayload(Server $server, array $payload): self
    {
        $system = $payload['system'] ?? [];
        $memory = $payload['memory'] ?? [];
        $load = $system['load'] ?? [];

        return static::create([
            'server_id' => $server->id,
            'recorded_at' => now(),
            'uptime' => $system['uptime'] ?? null,
            'load_1m' => $load['1m'] ?? null,
            'load_5m' => $load['5m'] ?? null,
            'load_15m' => $load['15m'] ?? null,
            'cpu_cores' => $system['cpu_cores'] ?? null,
            'memory_total' => $memory['mem']['total'] ?? null,
            'memory_used' => $memory['mem']['used'] ?? null,
            'memory_available' => $memory['mem']['available'] ?? null,
            'swap_total' => $memory['swap']['total'] ?? null,
            'swap_used' => $memory['swap']['used'] ?? null,
            'disks' => $payload['disks'] ?? null,
            'services' => $payload['services'] ?? null,
            'security' => $payload['security'] ?? null,
        ]);
    }
}
