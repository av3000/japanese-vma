<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * `/mcp/study` exists only while `study_mcp.enabled` is true. Routes are registered when the
 * application boots, so the disabled case sets the variable and boots a fresh application.
 */
final class StudyServerRouteTest extends TestCase
{
    private const HEADERS = ['Accept' => 'application/json, text/event-stream'];

    private string|false $originalSetting = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSetting = getenv('STUDY_MCP_ENABLED');
    }

    protected function tearDown(): void
    {
        $this->setStudyMcpEnabled($this->originalSetting === false ? null : $this->originalSetting);

        parent::tearDown();
    }

    public function test_the_server_lists_only_read_only_tools_and_needs_no_csrf_token(): void
    {
        $this->assertTrue(config('study_mcp.enabled'));

        $initialize = $this->rpc('initialize', [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'phpunit', 'version' => '1'],
        ]);

        $initialize->assertOk()->assertJsonPath('result.serverInfo.name', 'Japanese study');

        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');

        $this->assertContains('lookup_word', array_column($tools, 'name'));
        $this->assertContains('lookup_kanji', array_column($tools, 'name'));

        foreach ($tools as $tool) {
            $this->assertTrue($tool['annotations']['readOnlyHint'], $tool['name'].' must be read-only');
            $this->assertArrayHasKey('inputSchema', $tool);
            $this->assertArrayHasKey('outputSchema', $tool);
        }
    }

    public function test_the_route_is_not_registered_when_the_setting_is_off(): void
    {
        $this->setStudyMcpEnabled('false');
        $this->refreshApplication();

        $this->assertFalse(config('study_mcp.enabled'));
        $this->rpc('tools/list')->assertNotFound();
    }

    /**
     * @param array<string, mixed> $params
     */
    private function rpc(string $method, array $params = []): TestResponse
    {
        return $this->postJson('/mcp/study', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params === [] ? (object) [] : $params,
        ], self::HEADERS);
    }

    private function setStudyMcpEnabled(?string $value): void
    {
        if ($value === null) {
            putenv('STUDY_MCP_ENABLED');
            unset($_ENV['STUDY_MCP_ENABLED'], $_SERVER['STUDY_MCP_ENABLED']);

            return;
        }

        putenv("STUDY_MCP_ENABLED={$value}");
        $_ENV['STUDY_MCP_ENABLED'] = $value;
        $_SERVER['STUDY_MCP_ENABLED'] = $value;
    }
}
