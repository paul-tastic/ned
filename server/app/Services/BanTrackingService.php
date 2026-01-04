<?php

namespace App\Services;

use App\Models\BannedIpEvent;
use App\Models\Server;

class BanTrackingService
{
    public function __construct(
        private GeoIpService $geoIpService
    ) {}

    /**
     * Process banned IPs from metrics payload and track ban/unban events.
     */
    public function processBannedIps(Server $server, array $security): void
    {
        $currentBannedIps = $this->extractIps($security['banned_ips'] ?? []);

        // Get previously tracked banned IPs for this server
        $previouslyBanned = $this->getCurrentlyBannedIps($server->id);

        // If neither current nor previous has any IPs, nothing to do
        if (empty($currentBannedIps) && empty($previouslyBanned)) {
            return;
        }

        // Detect new bans (in current but not in previous)
        $newBans = array_diff($currentBannedIps, $previouslyBanned);

        // Detect unbans (in previous but not in current)
        $unbanned = array_diff($previouslyBanned, $currentBannedIps);

        // Record new ban events
        if (!empty($newBans)) {
            $this->recordBanEvents($server, $newBans);
        }

        // Record unban events
        if (!empty($unbanned)) {
            $this->recordUnbanEvents($server, $unbanned);
        }
    }

    /**
     * Extract IP addresses from banned_ips array.
     * Handles both old format (array of strings) and new format (array of objects).
     */
    private function extractIps(array $bannedIps): array
    {
        return collect($bannedIps)
            ->map(fn ($item) => is_array($item) ? ($item['ip'] ?? null) : $item)
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get IPs that are currently banned (have a ban event without a subsequent unban).
     */
    private function getCurrentlyBannedIps(int $serverId): array
    {
        // Get all IPs that have been banned
        $bannedIps = BannedIpEvent::where('server_id', $serverId)
            ->where('event_type', 'ban')
            ->distinct()
            ->pluck('ip_address')
            ->toArray();

        // Filter to only those still banned (latest event is 'ban')
        return collect($bannedIps)->filter(function ($ip) use ($serverId) {
            $latestEvent = BannedIpEvent::where('server_id', $serverId)
                ->where('ip_address', $ip)
                ->latest('event_at')
                ->first();

            return $latestEvent && $latestEvent->event_type === 'ban';
        })->values()->toArray();
    }

    /**
     * Record ban events for new IPs.
     */
    private function recordBanEvents(Server $server, array $ips): void
    {
        // Lookup geo data for all IPs
        $geoData = $this->geoIpService->lookupMany($ips);

        foreach ($ips as $ip) {
            $geo = $geoData[$ip] ?? null;

            BannedIpEvent::create([
                'server_id' => $server->id,
                'ip_address' => $ip,
                'event_type' => 'ban',
                'jail' => 'sshd', // Default, could be extended if agent sends jail info
                'country_code' => $geo['country_code'] ?? null,
                'country' => $geo['country'] ?? null,
                'city' => $geo['city'] ?? null,
                'isp' => $geo['isp'] ?? null,
                'event_at' => now(),
            ]);
        }
    }

    /**
     * Record unban events for IPs no longer banned.
     */
    private function recordUnbanEvents(Server $server, array $ips): void
    {
        foreach ($ips as $ip) {
            // Get geo from the last ban event (no need to re-lookup)
            $lastBan = BannedIpEvent::where('server_id', $server->id)
                ->where('ip_address', $ip)
                ->where('event_type', 'ban')
                ->latest('event_at')
                ->first();

            BannedIpEvent::create([
                'server_id' => $server->id,
                'ip_address' => $ip,
                'event_type' => 'unban',
                'jail' => $lastBan->jail ?? 'sshd',
                'country_code' => $lastBan->country_code ?? null,
                'country' => $lastBan->country ?? null,
                'city' => $lastBan->city ?? null,
                'isp' => $lastBan->isp ?? null,
                'event_at' => now(),
            ]);
        }
    }
}
