<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_successful_response(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'healthy')
            ->assertJsonPath('data.services.database', 'ok')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'services' => ['database'],
                    'timestamp',
                ],
            ]);
    }
}
