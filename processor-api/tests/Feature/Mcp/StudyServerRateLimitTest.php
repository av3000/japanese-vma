<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * `/mcp/study` is throttled per client address by the `mcp` limiter. The limit is read when a
 * request arrives, so a test lowers it through config without rebooting the application.
 */
final class StudyServerRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private const HEADERS = ['Accept' => 'application/json, text/event-stream'];

    private string|false $originalRate = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRate = getenv('STUDY_MCP_RATE_PER_MINUTE');
    }

    protected function tearDown(): void
    {
        $this->setRateVariable($this->originalRate === false ? null : $this->originalRate);

        parent::tearDown();
    }

    public function test_the_fourth_request_in_a_minute_from_one_address_is_rejected_with_retry_after(): void
    {
        config(['study_mcp.rate_per_minute' => 3]);

        foreach (range(1, 3) as $attempt) {
            $this->toolsList('203.0.113.10')->assertOk();
        }

        $rejected = $this->toolsList('203.0.113.10');

        $rejected->assertStatus(429);
        $this->assertTrue($rejected->headers->has('Retry-After'), 'a 429 must say when to retry');
        $this->assertGreaterThan(0, (int) $rejected->headers->get('Retry-After'));
    }

    public function test_another_address_is_not_affected_by_a_spent_limit(): void
    {
        config(['study_mcp.rate_per_minute' => 3]);

        foreach (range(1, 4) as $attempt) {
            $this->toolsList('203.0.113.20');
        }

        $this->toolsList('203.0.113.20')->assertStatus(429);
        $this->toolsList('203.0.113.21')->assertOk();
    }

    public function test_the_rate_is_30_a_minute_when_the_variable_is_unset(): void
    {
        $this->setRateVariable(null);

        $this->assertSame(30, $this->loadConfig()['rate_per_minute']);
    }

    public function test_the_rate_is_read_from_the_variable(): void
    {
        $this->setRateVariable('7');

        $this->assertSame(7, $this->loadConfig()['rate_per_minute']);
    }

    public function test_a_lookup_word_call_is_answered_through_the_throttled_route(): void
    {
        DB::table('japanese_word_bank_long')->insert([
            'id' => 1,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1001',
            'word' => '学校',
            'furigana' => 'がっこう',
            'jlpt' => '5',
            'word_type' => 'noun',
            'word_k_ele' => '学校',
            'furigana_r_ele' => 'がっこう',
            'sense' => json_encode([[['gloss', ['school']]]], JSON_THROW_ON_ERROR),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])->postJson('/mcp/study', [
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/call',
            'params' => ['name' => 'lookup_word', 'arguments' => ['query' => 'school']],
        ], self::HEADERS);

        $response->assertOk()
            ->assertJsonPath('result.structuredContent.total', 1)
            ->assertJsonPath('result.structuredContent.results.0.word', '学校');
        $this->assertSame('29', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_the_study_route_carries_the_mcp_throttle(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === 'mcp/study' && in_array('POST', $route->methods(), true));

        $this->assertNotNull($route);
        $this->assertContains('throttle:mcp', $route->gatherMiddleware());
    }

    private function toolsList(string $address): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $address])->postJson('/mcp/study', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => (object) [],
        ], self::HEADERS);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        return require config_path('study_mcp.php');
    }

    private function setRateVariable(?string $value): void
    {
        if ($value === null) {
            putenv('STUDY_MCP_RATE_PER_MINUTE');
            unset($_ENV['STUDY_MCP_RATE_PER_MINUTE'], $_SERVER['STUDY_MCP_RATE_PER_MINUTE']);

            return;
        }

        putenv("STUDY_MCP_RATE_PER_MINUTE={$value}");
        $_ENV['STUDY_MCP_RATE_PER_MINUTE'] = $value;
        $_SERVER['STUDY_MCP_RATE_PER_MINUTE'] = $value;
    }
}
