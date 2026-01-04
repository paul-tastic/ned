<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedIpEvent extends Model
{
    protected $fillable = [
        'server_id',
        'ip_address',
        'event_type',
        'jail',
        'country_code',
        'country',
        'city',
        'isp',
        'event_at',
    ];

    protected $casts = [
        'event_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get ban count for a specific IP on a server.
     */
    public static function getBanCount(int $serverId, string $ip): int
    {
        return static::where('server_id', $serverId)
            ->where('ip_address', $ip)
            ->where('event_type', 'ban')
            ->count();
    }

    /**
     * Get ban counts for multiple IPs on a server.
     */
    public static function getBanCounts(int $serverId, array $ips): array
    {
        if (empty($ips)) {
            return [];
        }

        return static::where('server_id', $serverId)
            ->whereIn('ip_address', $ips)
            ->where('event_type', 'ban')
            ->selectRaw('ip_address, COUNT(*) as ban_count')
            ->groupBy('ip_address')
            ->pluck('ban_count', 'ip_address')
            ->toArray();
    }
}
