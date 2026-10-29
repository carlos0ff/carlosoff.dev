<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_trusted-proxies-probe', fn () => url('/'));
    }

    public function test_urls_use_the_forwarded_scheme_and_host_from_a_local_proxy(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '172.27.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'carlos0ff.dev',
                'X-Forwarded-Port' => '443',
            ])
            ->get('/_trusted-proxies-probe');

        $response->assertOk();
        $this->assertSame('https://carlos0ff.dev', $response->getContent());
    }

    public function test_forwarded_headers_from_a_public_address_are_ignored(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'evil.example',
            ])
            ->get('/_trusted-proxies-probe');

        $response->assertOk();
        $this->assertStringNotContainsString('evil.example', $response->getContent());
    }
}
