<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'hostname',
        'token',
        'status',
        'last_seen_at',
        'is_active',
        'agent_version',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Generate a new server token.
     * Returns the plain token (show once to user), stores hashed version.
     */
    public static function generateToken(): array
    {
        $plain = Str::random(64);
        $hashed = hash('sha256', $plain);

        return [
            'plain' => $plain,
            'hashed' => $hashed,
        ];
    }

    /**
     * Find a server by its plain token.
     */
    public static function findByToken(string $plainToken): ?self
    {
        $hashed = hash('sha256', $plainToken);

        return static::where('token', $hashed)->first();
    }

    /**
     * Mark the server as seen (updates last_seen_at).
     */
    public function markAsSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Update server status based on current conditions.
     */
    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * Check if server is considered offline (no metrics in last 5 minutes).
     */
    public function isOffline(): bool
    {
        return $this->last_seen_at === null
            || $this->last_seen_at->lt(now()->subMinutes(5));
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function thresholds(): HasMany
    {
        return $this->hasMany(Threshold::class);
    }

    /**
     * Get the latest metric for this server.
     */
    public function latestMetric(): ?Metric
    {
        return $this->metrics()->latest('recorded_at')->first();
    }

    /**
     * Get active (unresolved) alerts for this server.
     */
    public function activeAlerts(): HasMany
    {
        return $this->alerts()->whereNull('resolved_at');
    }
}
