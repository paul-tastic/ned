<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\BannedIpEvent;
use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo data for screenshots.
     *
     * Usage: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        // Create a demo user if needed
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => bcrypt('password')]
        );

        $this->command->info('Creating demo servers...');

        // Server 1: Healthy production web server
        $webServer = $this->createServer($user, [
            'name' => 'web-prod-01',
            'hostname' => 'web-prod-01.example.com',
            'status' => 'online',
            'agent_version' => '0.3.0',
            'last_seen_at' => now()->subSeconds(30),
        ]);
        $this->createHealthyMetrics($webServer);

        // Server 2: Warning state - high memory usage
        $appServer = $this->createServer($user, [
            'name' => 'app-prod-01',
            'hostname' => 'app-prod-01.example.com',
            'status' => 'warning',
            'agent_version' => '0.3.0',
            'last_seen_at' => now()->subSeconds(45),
        ]);
        $this->createWarningMetrics($appServer);
        $this->createAlert($appServer, [
            'metric' => 'memory_percent',
            'level' => 'warning',
            'value' => 82.5,
            'threshold' => 80.0,
            'message' => 'Memory usage is 82.5% (threshold: 80%)',
        ]);

        // Server 3: Critical state - disk almost full
        $dbServer = $this->createServer($user, [
            'name' => 'db-prod-01',
            'hostname' => 'db-prod-01.example.com',
            'status' => 'critical',
            'agent_version' => '0.2.9',
            'last_seen_at' => now()->subMinutes(1),
        ]);
        $this->createCriticalMetrics($dbServer);
        $this->createAlert($dbServer, [
            'metric' => 'disk_percent',
            'level' => 'critical',
            'value' => 94.2,
            'threshold' => 90.0,
            'message' => 'Disk /var/lib/mysql is 94.2% full (threshold: 90%)',
        ]);

        // Server 4: Offline (hasn't reported in a while)
        $this->createServer($user, [
            'name' => 'staging-01',
            'hostname' => 'staging.example.com',
            'status' => 'offline',
            'agent_version' => '0.2.8',
            'last_seen_at' => now()->subHours(2),
        ]);

        // Add some banned IP events for security display
        $this->createBannedIpEvents($webServer);
        $this->createBannedIpEvents($appServer);

        // Add a resolved alert for history
        $this->createAlert($webServer, [
            'metric' => 'cpu_load',
            'level' => 'warning',
            'value' => 85.0,
            'threshold' => 80.0,
            'message' => 'CPU load was 85% (threshold: 80%)',
            'resolved_at' => now()->subHours(3),
            'notified_at' => now()->subHours(4),
        ]);

        $this->command->info('Demo data created successfully!');
        $this->command->info('Login: demo@example.com / password');
    }

    private function createServer(User $user, array $attributes): Server
    {
        $token = Server::generateToken();

        return Server::create([
            'user_id' => $user->id,
            'token' => $token['hashed'],
            ...$attributes,
        ]);
    }

    private function createHealthyMetrics(Server $server): void
    {
        // Create metrics for the last 2 hours
        for ($i = 0; $i < 24; $i++) {
            Metric::create([
                'server_id' => $server->id,
                'recorded_at' => now()->subMinutes($i * 5),
                'uptime' => 864000 + ($i * 300), // ~10 days
                'load_1m' => $this->randomFloat(0.2, 1.5),
                'load_5m' => $this->randomFloat(0.3, 1.2),
                'load_15m' => $this->randomFloat(0.4, 1.0),
                'cpu_cores' => 4,
                'memory_total' => 8192,
                'memory_used' => rand(3000, 4500),
                'memory_available' => rand(3500, 5000),
                'swap_total' => 2048,
                'swap_used' => rand(100, 400),
                'disks' => [
                    ['mount' => '/', 'total_mb' => 102400, 'used_mb' => rand(35840, 46080), 'percent' => rand(35, 45)],
                    ['mount' => '/var/log', 'total_mb' => 51200, 'used_mb' => rand(10240, 20480), 'percent' => rand(20, 40)],
                ],
                'network' => [
                    ['interface' => 'eth0', 'rx_bytes' => rand(100000000, 500000000), 'tx_bytes' => rand(50000000, 200000000)],
                ],
                'services' => [
                    ['name' => 'nginx', 'status' => 'running'],
                    ['name' => 'php-fpm', 'status' => 'running'],
                    ['name' => 'redis', 'status' => 'running'],
                ],
                'security' => $this->generateSecurityData(rand(50, 150), rand(2, 5)),
            ]);
        }
    }

    private function createWarningMetrics(Server $server): void
    {
        for ($i = 0; $i < 24; $i++) {
            $memoryUsed = $i < 6 ? rand(6200, 6800) : rand(4000, 5000); // Recent high memory

            Metric::create([
                'server_id' => $server->id,
                'recorded_at' => now()->subMinutes($i * 5),
                'uptime' => 432000 + ($i * 300), // ~5 days
                'load_1m' => $this->randomFloat(1.5, 3.0),
                'load_5m' => $this->randomFloat(1.2, 2.5),
                'load_15m' => $this->randomFloat(1.0, 2.0),
                'cpu_cores' => 4,
                'memory_total' => 8192,
                'memory_used' => $memoryUsed,
                'memory_available' => 8192 - $memoryUsed,
                'swap_total' => 2048,
                'swap_used' => rand(500, 1000),
                'disks' => [
                    ['mount' => '/', 'total_mb' => 102400, 'used_mb' => rand(56320, 66560), 'percent' => rand(55, 65)],
                    ['mount' => '/home', 'total_mb' => 204800, 'used_mb' => rand(81920, 102400), 'percent' => rand(40, 50)],
                ],
                'network' => [
                    ['interface' => 'eth0', 'rx_bytes' => rand(200000000, 800000000), 'tx_bytes' => rand(100000000, 400000000)],
                ],
                'services' => [
                    ['name' => 'nginx', 'status' => 'running'],
                    ['name' => 'php-fpm', 'status' => 'running'],
                    ['name' => 'mysql', 'status' => 'running'],
                    ['name' => 'supervisor', 'status' => 'running'],
                ],
                'security' => $this->generateSecurityData(rand(200, 500), rand(5, 12)),
            ]);
        }
    }

    private function createCriticalMetrics(Server $server): void
    {
        for ($i = 0; $i < 24; $i++) {
            $diskPercent = $i < 6 ? rand(92, 96) : rand(85, 91); // Recent disk spike

            Metric::create([
                'server_id' => $server->id,
                'recorded_at' => now()->subMinutes($i * 5),
                'uptime' => 2592000 + ($i * 300), // ~30 days
                'load_1m' => $this->randomFloat(0.5, 2.0),
                'load_5m' => $this->randomFloat(0.6, 1.8),
                'load_15m' => $this->randomFloat(0.7, 1.5),
                'cpu_cores' => 8,
                'memory_total' => 32768,
                'memory_used' => rand(18000, 22000),
                'memory_available' => rand(10000, 14000),
                'swap_total' => 4096,
                'swap_used' => rand(200, 600),
                'disks' => [
                    ['mount' => '/', 'total_mb' => 51200, 'used_mb' => rand(20480, 30720), 'percent' => rand(40, 60)],
                    ['mount' => '/var/lib/mysql', 'total_mb' => 512000, 'used_mb' => (int) (512000 * $diskPercent / 100), 'percent' => $diskPercent],
                ],
                'network' => [
                    ['interface' => 'eth0', 'rx_bytes' => rand(500000000, 2000000000), 'tx_bytes' => rand(300000000, 1000000000)],
                ],
                'services' => [
                    ['name' => 'mysql', 'status' => 'running'],
                    ['name' => 'mysqld-exporter', 'status' => 'running'],
                ],
                'security' => [
                    'fail2ban' => ['status' => 'active', 'banned_ips' => rand(0, 3)],
                    'ufw' => ['status' => 'active'],
                ],
            ]);
        }
    }

    private function createAlert(Server $server, array $attributes): Alert
    {
        return Alert::create([
            'server_id' => $server->id,
            'created_at' => $attributes['resolved_at'] ?? now()->subMinutes(rand(5, 30)),
            ...$attributes,
        ]);
    }

    private function createBannedIpEvents(Server $server): void
    {
        $countries = [
            ['code' => 'CN', 'name' => 'China', 'city' => 'Beijing'],
            ['code' => 'RU', 'name' => 'Russia', 'city' => 'Moscow'],
            ['code' => 'BR', 'name' => 'Brazil', 'city' => 'São Paulo'],
            ['code' => 'IN', 'name' => 'India', 'city' => 'Mumbai'],
            ['code' => 'VN', 'name' => 'Vietnam', 'city' => 'Hanoi'],
        ];

        $jails = ['sshd', 'nginx-http-auth', 'postfix'];

        for ($i = 0; $i < rand(8, 15); $i++) {
            $country = $countries[array_rand($countries)];

            BannedIpEvent::create([
                'server_id' => $server->id,
                'ip_address' => rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
                'event_type' => 'ban',
                'jail' => $jails[array_rand($jails)],
                'country_code' => $country['code'],
                'country' => $country['name'],
                'city' => $country['city'],
                'isp' => 'Example ISP ' . rand(1, 50),
                'event_at' => now()->subHours(rand(1, 48)),
            ]);
        }
    }

    private function randomFloat(float $min, float $max): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
    }
}
