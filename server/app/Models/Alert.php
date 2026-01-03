<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'metric',
        'level',
        'value',
        'threshold',
        'message',
        'notified_at',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'threshold' => 'decimal:2',
        'notified_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relationships

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeWarning($query)
    {
        return $query->where('level', 'warning');
    }

    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    // Actions

    /**
     * Acknowledge this alert.
     */
    public function acknowledge(): void
    {
        $this->update(['acknowledged_at' => now()]);
    }

    /**
     * Resolve this alert.
     */
    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    /**
     * Mark this alert as notified.
     */
    public function markNotified(): void
    {
        $this->update(['notified_at' => now()]);
    }

    // Computed

    public function isActive(): bool
    {
        return $this->resolved_at === null;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function isCritical(): bool
    {
        return $this->level === 'critical';
    }

    /**
     * Create an alert for a threshold violation.
     */
    public static function createFromThreshold(
        Server $server,
        Threshold $threshold,
        float $currentValue,
        string $level
    ): self {
        return static::create([
            'server_id' => $server->id,
            'metric' => $threshold->metric,
            'level' => $level,
            'value' => $currentValue,
            'threshold' => $level === 'critical'
                ? $threshold->critical_value
                : $threshold->warning_value,
            'message' => sprintf(
                '%s is %s (threshold: %s)',
                $threshold->metric,
                $currentValue,
                $level === 'critical' ? $threshold->critical_value : $threshold->warning_value
            ),
        ]);
    }
}
