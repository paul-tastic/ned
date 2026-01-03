<?php

namespace App\Support;

class ServiceInfo
{
    protected static array $services = [
        // Web servers
        'nginx' => 'Web server and reverse proxy',
        'apache2' => 'Apache HTTP web server',
        'httpd' => 'Apache HTTP web server (RHEL)',
        'caddy' => 'Automatic HTTPS web server',
        'lighttpd' => 'Lightweight web server',

        // PHP
        'php-fpm' => 'PHP FastCGI process manager',
        'php8.3-fpm' => 'PHP 8.3 FastCGI process manager',
        'php8.2-fpm' => 'PHP 8.2 FastCGI process manager',
        'php8.1-fpm' => 'PHP 8.1 FastCGI process manager',
        'php8.0-fpm' => 'PHP 8.0 FastCGI process manager',
        'php7.4-fpm' => 'PHP 7.4 FastCGI process manager',

        // Databases
        'mysql' => 'MySQL database server',
        'mysqld' => 'MySQL database server',
        'mariadb' => 'MariaDB database server',
        'postgresql' => 'PostgreSQL database server',
        'postgres' => 'PostgreSQL database server',
        'redis' => 'Redis in-memory data store',
        'redis-server' => 'Redis in-memory data store',
        'memcached' => 'Memcached caching server',
        'mongodb' => 'MongoDB document database',
        'mongod' => 'MongoDB database server',

        // Mail
        'postfix' => 'Mail transfer agent (SMTP)',
        'dovecot' => 'IMAP/POP3 mail server',
        'exim' => 'Mail transfer agent',
        'sendmail' => 'Mail transfer agent',
        'opendkim' => 'DKIM email signing service',

        // System services
        'sshd' => 'Secure Shell (SSH) server',
        'ssh' => 'Secure Shell (SSH) server',
        'cron' => 'Scheduled task runner',
        'crond' => 'Scheduled task runner',
        'rsyslog' => 'System logging service',
        'syslog-ng' => 'System logging service',
        'systemd-journald' => 'Journal logging service',
        'dbus' => 'Message bus system',
        'udev' => 'Device manager',
        'NetworkManager' => 'Network management daemon',
        'networkd' => 'Network management daemon',
        'auditd' => 'System auditing daemon',
        'irqbalance' => 'IRQ balancing daemon',
        'tuned' => 'Dynamic system tuning daemon',
        'rpcbind' => 'RPC port mapper service',
        'gssproxy' => 'GSSAPI credential proxy',
        'rc-local' => 'Legacy startup script runner',

        // Security
        'fail2ban' => 'Intrusion prevention (bans IPs)',
        'ufw' => 'Uncomplicated Firewall',
        'firewalld' => 'Dynamic firewall manager',
        'iptables' => 'Firewall rules manager',

        // Containers & orchestration
        'docker' => 'Container runtime',
        'containerd' => 'Container runtime',
        'dockerd' => 'Docker daemon',
        'kubelet' => 'Kubernetes node agent',

        // Monitoring & logging
        'prometheus' => 'Metrics collection and monitoring',
        'grafana' => 'Metrics visualization dashboard',
        'node_exporter' => 'Hardware/OS metrics exporter',
        'elasticsearch' => 'Search and analytics engine',
        'logstash' => 'Log processing pipeline',
        'kibana' => 'Elasticsearch visualization',
        'filebeat' => 'Log file shipper',

        // Queue & messaging
        'rabbitmq-server' => 'Message broker',
        'rabbitmq' => 'Message broker',

        // Laravel specific
        'supervisor' => 'Process control system',
        'supervisord' => 'Process control system',
        'laravel-worker' => 'Laravel queue worker',
        'horizon' => 'Laravel Horizon queue manager',

        // Misc
        'ntpd' => 'Network time sync',
        'chronyd' => 'Network time sync',
        'ntp' => 'Network time sync',
        'cups' => 'Print server',
        'avahi-daemon' => 'mDNS/DNS-SD service discovery',
        'bluetooth' => 'Bluetooth service',
        'snapd' => 'Snap package manager',
        'qemu-guest-agent' => 'QEMU VM guest agent',

        // FTP
        'pure-ftpd' => 'Pure-FTPd file transfer server',
        'vsftpd' => 'Very Secure FTP daemon',
        'proftpd' => 'ProFTPD file transfer server',

        // DNS
        'pdns' => 'PowerDNS server',
        'named' => 'BIND DNS server',
        'unbound' => 'DNS resolver',

        // Hosting panels (LiteSpeed/CyberPanel)
        'lshttpd' => 'LiteSpeed web server',
        'lscpd' => 'LiteSpeed control panel daemon',
        'lsmcd' => 'LiteSpeed memcached replacement',

        // Security agents
        'monarx-agent' => 'Monarx security monitoring agent',
    ];

    public static function get(string $serviceName): ?string
    {
        // Direct match
        if (isset(static::$services[$serviceName])) {
            return static::$services[$serviceName];
        }

        // Try lowercase
        $lower = strtolower($serviceName);
        if (isset(static::$services[$lower])) {
            return static::$services[$lower];
        }

        // Check for partial matches (e.g., php8.3-fpm matches php*-fpm pattern)
        foreach (static::$services as $key => $description) {
            if (str_contains($serviceName, $key) || str_contains($key, $serviceName)) {
                return $description;
            }
        }

        return null;
    }

    public static function all(): array
    {
        return static::$services;
    }
}
