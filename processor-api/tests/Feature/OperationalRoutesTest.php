<?php

namespace Tests\Feature;

use Tests\TestCase;

class OperationalRoutesTest extends TestCase
{
    public function testHealthEndpointReturnsExpectedJson(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertExactJson(['ok' => true]);
    }
}
