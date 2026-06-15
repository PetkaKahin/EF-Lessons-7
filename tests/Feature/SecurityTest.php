<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_cors_preflight_uses_configured_origin(): void
    {
        config([
            'cors.allowed_origins' => ['https://frontend.example'],
            'cors.supports_credentials' => false,
        ]);

        $response = $this->withHeaders([
            'Origin' => 'https://frontend.example',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization, X-Request-Id',
        ])->options('/api/tasks');

        $response
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://frontend.example');
    }

    public function test_api_routes_are_rate_limited(): void
    {
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(2)->by('security-test'));
        RateLimiter::clear('security-test');

        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable();

        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable();

        $this->postJson('/api/auth/login', [])
            ->assertStatus(429);
    }

    public function test_exception_details_are_hidden_when_debug_is_disabled(): void
    {
        config([
            'app.debug' => false,
            'app.env' => 'production',
        ]);

        Route::get('/__security-test-error', static function (): void {
            throw new RuntimeException('sensitive production details');
        });

        $response = $this->get('/__security-test-error');

        $response->assertStatus(500);
        $this->assertStringNotContainsString(
            'sensitive production details',
            (string) $response->getContent()
        );
    }
}
