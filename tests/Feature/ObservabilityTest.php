<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    public function test_health_endpoint_includes_request_id_header(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertHeader('X-Request-Id');
    }

    public function test_ready_endpoint_returns_unavailable_when_database_is_down(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->andThrow(new RuntimeException('Database is down.'));

        $this->getJson('/ready')
            ->assertStatus(503)
            ->assertJson([
                'database' => 'unavailable',
                'status' => 'error',
            ]);
    }

    public function test_metrics_endpoint_returns_request_count_and_response_time(): void
    {
        $this->getJson('/health')->assertOk();

        $response = $this->get('/metrics');

        $response
            ->assertOk()
            ->assertSeeText('Application metrics')
            ->assertSeeText('Total requests')
            ->assertSeeText('1')
            ->assertSeeText('Average response time');

        $this->assertDatabaseHas('request_metrics', [
            'method' => 'GET',
            'path' => 'health',
            'status_code' => 200,
        ]);
    }
}
