<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Threshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'server_id',
        'metric',
        'warning_value',
        'critical_value',
        'comparison',
        'is_active',
    ];

    protected $casts = [
        'warning_value' => 'decimal:2',
        'critical_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Default thresholds for new users
    public const DEFAULTS = [
        'cpu_load' => ['warning' => 70, 'critical' => 90, 'comparison' => '>'],
        'memory_percent' => ['warning' => 80, 'critical' => 95, 'comparison' => '>'],
        'disk_percent' => ['warning' => 80, 'critical' => 95, 'comparison' => '>'],
        'swap_percent' => ['warning' => 50, 'critical' => 80, 'comparison' => '>'],
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    // Logic

    /**
     * Check if a value violates this threshold.
     * Returns: 'critical', 'warning', or null
     */
    public function check(float $value): ?string
    {
        if (! $this->is_active) {
            return null;
        }

        if ($this->violates($value, $this->critical_value)) {
            return 'critical';
        }

        if ($this->violates($value, $this->warning_value)) {
            return 'warning';
        }

        return null;
    }

    /**
     * Check if value violates a threshold value based on comparison operator.
     */
    protected function violates(float $value, float $threshold): bool
    {
        return match ($this->comparison) {
            '>' => $value > $threshold,
            '<' => $value < $threshold,
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            '==' => $value == $threshold,
            default => false,
        };
    }

    /**
     * Get the applicable threshold for a server + metric.
     * Server-specific threshold takes precedence over global (null server_id).
     */
    public static function getForServerMetric(int $userId, int $serverId, string $metric): ?self
    {
        // First try server-specific
        $threshold = static::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->where('metric', $metric)
            ->where('is_active', true)
            ->first();

        if ($threshold) {
            return $threshold;
        }

        // Fall back to global (null server_id)
        return static::where('user_id', $userId)
            ->whereNull('server_id')
            ->where('metric', $metric)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create default thresholds for a user.
     */
    public static function createDefaultsForUser(User $user): void
    {
        foreach (self::DEFAULTS as $metric => $values) {
            static::create([
                'user_id' => $user->id,
                'server_id' => null,
                'metric' => $metric,
                'warning_value' => $values['warning'],
                'critical_value' => $values['critical'],
                'comparison' => $values['comparison'],
                'is_active' => true,
            ]);
        }
    }
}
