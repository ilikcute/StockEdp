<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Pastikan aplikasi merespons request dengan benar.
     * Test ini menggunakan endpoint API agar tidak bergantung pada Vite manifest.
     */
    public function test_the_application_is_running(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
    }
}
