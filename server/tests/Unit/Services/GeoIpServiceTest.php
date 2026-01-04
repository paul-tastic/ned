<?php

namespace Tests\Unit\Services;

use App\Services\GeoIpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoIpServiceTest extends TestCase
{
    private GeoIpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeoIpService;
        Cache::flush();
    }

    public function test_lookup_returns_geo_data_for_valid_public_ip(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'countryCode' => 'US',
                'city' => 'New York',
                'isp' => 'Example ISP',
            ]),
        ]);

        $result = $this->service->lookup('8.8.8.8');

        $this->assertIsArray($result);
        $this->assertEquals('United States', $result['country']);
        $this->assertEquals('US', $result['country_code']);
        $this->assertEquals('New York', $result['city']);
        $this->assertEquals('Example ISP', $result['isp']);
    }

    public function test_lookup_returns_null_for_private_ip(): void
    {
        Http::fake();

        $result = $this->service->lookup('192.168.1.1');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_lookup_returns_null_for_localhost(): void
    {
        Http::fake();

        $result = $this->service->lookup('127.0.0.1');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_lookup_returns_null_for_10_network(): void
    {
        Http::fake();

        $result = $this->service->lookup('10.0.0.1');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_lookup_returns_null_on_api_failure(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'fail',
                'message' => 'invalid query',
            ]),
        ]);

        $result = $this->service->lookup('8.8.8.8');

        $this->assertNull($result);
    }

    public function test_lookup_returns_null_on_http_error(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([], 500),
        ]);

        $result = $this->service->lookup('8.8.8.8');

        $this->assertNull($result);
    }

    public function test_lookup_caches_results(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Germany',
                'countryCode' => 'DE',
                'city' => 'Berlin',
                'isp' => 'Test ISP',
            ]),
        ]);

        // First call
        $this->service->lookup('8.8.8.8');

        // Second call should use cache
        $result = $this->service->lookup('8.8.8.8');

        Http::assertSentCount(1);
        $this->assertEquals('Germany', $result['country']);
    }

    public function test_lookup_many_returns_results_for_multiple_ips(): void
    {
        Http::fake([
            'ip-api.com/json/1.1.1.1*' => Http::response([
                'status' => 'success',
                'country' => 'Australia',
                'countryCode' => 'AU',
                'city' => 'Sydney',
                'isp' => 'Cloudflare',
            ]),
            'ip-api.com/json/8.8.8.8*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'countryCode' => 'US',
                'city' => 'Mountain View',
                'isp' => 'Google',
            ]),
        ]);

        $results = $this->service->lookupMany(['1.1.1.1', '8.8.8.8']);

        $this->assertArrayHasKey('1.1.1.1', $results);
        $this->assertArrayHasKey('8.8.8.8', $results);
        $this->assertEquals('Australia', $results['1.1.1.1']['country']);
        $this->assertEquals('United States', $results['8.8.8.8']['country']);
    }

    public function test_lookup_many_skips_private_ips(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'countryCode' => 'US',
                'city' => 'Test',
                'isp' => 'Test',
            ]),
        ]);

        $results = $this->service->lookupMany(['192.168.1.1', '8.8.8.8']);

        $this->assertNull($results['192.168.1.1']);
        $this->assertNotNull($results['8.8.8.8']);
        Http::assertSentCount(1);
    }

    public function test_lookup_handles_timeout_gracefully(): void
    {
        Http::fake([
            'ip-api.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $result = $this->service->lookup('8.8.8.8');

        $this->assertNull($result);
    }

    public function test_lookup_many_returns_empty_array_for_empty_input(): void
    {
        $results = $this->service->lookupMany([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
