<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_the_service_as_available(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'eventosusfa-api')
            ->assertJsonStructure(['status', 'service', 'timestamp']);
    }
}
