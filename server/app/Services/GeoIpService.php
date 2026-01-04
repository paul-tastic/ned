<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoIpService
{
    /**
     * Get geolocation data for an IP address.
     * Uses ip-api.com (free, no key required for low volume).
     * Results are cached for 24 hours.
     */
    public function lookup(string $ip): ?array
    {
        // Skip private/local IPs
        if ($this->isPrivateIp($ip)) {
            return null;
        }

        return Cache::remember("geoip:{$ip}", now()->addDay(), function () use ($ip) {
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,countryCode,city,isp',
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    return [
                        'country' => $response->json('country'),
                        'country_code' => $response->json('countryCode'),
                        'city' => $response->json('city'),
                        'isp' => $response->json('isp'),
                    ];
                }
            } catch (\Exception $e) {
                // Silently fail - geo is nice to have, not critical
            }

            return null;
        });
    }

    /**
     * Batch lookup multiple IPs.
     */
    public function lookupMany(array $ips): array
    {
        $results = [];
        foreach ($ips as $ip) {
            $results[$ip] = $this->lookup($ip);
        }
        return $results;
    }

    /**
     * Check if IP is private/local.
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
