<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The Header search reads the counts response flat (no `data` wrapper) and treats a `null` count
 * as "that list rejects the keyword". Both come from choices in SearchCountsResource ($wrap = null,
 * the null-preserving casts) that a refactor could drop without any test noticing.
 */
class SearchCountsResourceOpenApiTest extends TestCase
{
    private const COUNT_KEYS = ['articles', 'kanji', 'words', 'sentences', 'radicals'];

    /**
     * @return array<string, mixed>
     */
    private function apiJson(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    public function test_the_response_is_the_flat_resource_component(): void
    {
        $this->assertSame(
            '#/components/schemas/SearchCountsResource',
            $this->apiJson()['paths']['/search/counts']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    public function test_every_count_is_a_required_nullable_integer(): void
    {
        $schema = $this->apiJson()['components']['schemas']['SearchCountsResource'];

        $this->assertEqualsCanonicalizing(['q', ...self::COUNT_KEYS], $schema['required']);
        $this->assertSame('string', $schema['properties']['q']['type']);

        foreach (self::COUNT_KEYS as $key) {
            $this->assertSame(['integer', 'null'], $schema['properties'][$key]['type'], $key);
        }
    }
}
