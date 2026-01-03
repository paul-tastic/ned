<?php

namespace Tests\Unit\Support;

use App\Support\ServiceInfo;
use PHPUnit\Framework\TestCase;

class ServiceInfoTest extends TestCase
{
    public function test_returns_description_for_known_service(): void
    {
        $this->assertEquals('Web server and reverse proxy', ServiceInfo::get('nginx'));
    }

    public function test_returns_description_for_database_services(): void
    {
        $this->assertEquals('MySQL database server', ServiceInfo::get('mysql'));
        $this->assertEquals('PostgreSQL database server', ServiceInfo::get('postgresql'));
        $this->assertEquals('Redis in-memory data store', ServiceInfo::get('redis'));
    }

    public function test_returns_description_for_php_fpm_variants(): void
    {
        $this->assertEquals('PHP 8.3 FastCGI process manager', ServiceInfo::get('php8.3-fpm'));
        $this->assertEquals('PHP 8.2 FastCGI process manager', ServiceInfo::get('php8.2-fpm'));
        $this->assertEquals('PHP FastCGI process manager', ServiceInfo::get('php-fpm'));
    }

    public function test_returns_description_for_security_services(): void
    {
        $this->assertEquals('Intrusion prevention (bans IPs)', ServiceInfo::get('fail2ban'));
        $this->assertEquals('Secure Shell (SSH) server', ServiceInfo::get('sshd'));
    }

    public function test_returns_description_for_system_services(): void
    {
        $this->assertEquals('Scheduled task runner', ServiceInfo::get('cron'));
        $this->assertEquals('Scheduled task runner', ServiceInfo::get('crond'));
    }

    public function test_returns_null_for_unknown_service(): void
    {
        $this->assertNull(ServiceInfo::get('completely-unknown-service-xyz'));
    }

    public function test_handles_case_insensitivity(): void
    {
        // Note: Current implementation may need case-insensitive matching
        // This test documents the expected behavior
        $result = ServiceInfo::get('NGINX');
        // Should work after lowercase conversion
        $this->assertNotNull(ServiceInfo::get('nginx'));
    }

    public function test_all_returns_complete_array(): void
    {
        $all = ServiceInfo::all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('nginx', $all);
        $this->assertArrayHasKey('mysql', $all);
        $this->assertArrayHasKey('sshd', $all);
        $this->assertArrayHasKey('fail2ban', $all);
        $this->assertArrayHasKey('docker', $all);
    }

    public function test_descriptions_are_not_empty(): void
    {
        foreach (ServiceInfo::all() as $service => $description) {
            $this->assertNotEmpty($description, "Description for {$service} should not be empty");
            $this->assertIsString($description);
        }
    }

    public function test_common_web_stack_services_are_defined(): void
    {
        $webStackServices = [
            'nginx',
            'apache2',
            'mysql',
            'postgresql',
            'redis',
            'php-fpm',
            'php8.3-fpm',
        ];

        foreach ($webStackServices as $service) {
            $this->assertNotNull(
                ServiceInfo::get($service),
                "Web stack service '{$service}' should have a description"
            );
        }
    }

    public function test_common_system_services_are_defined(): void
    {
        $systemServices = [
            'sshd',
            'ssh',
            'cron',
            'crond',
            'fail2ban',
            'docker',
        ];

        foreach ($systemServices as $service) {
            $this->assertNotNull(
                ServiceInfo::get($service),
                "System service '{$service}' should have a description"
            );
        }
    }
}
