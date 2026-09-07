<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ArticleModerationOpenApiTest extends TestCase
{
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

    public function test_moderation_paths_use_static_queue_and_uuid_status_mutation(): void
    {
        $apiJson = $this->apiJson();

        $this->assertArrayHasKey('/articles/pending', $apiJson['paths']);
        $this->assertArrayHasKey('/articles/{uuid}/status', $apiJson['paths']);

        $parameter = $apiJson['paths']['/articles/{uuid}/status']['post']['parameters'][0];

        $this->assertSame('uuid', $parameter['name']);
        $this->assertSame('path', $parameter['in']);
        $this->assertSame('uuid', $parameter['schema']['format']);
    }

    public function test_moderation_schemas_keep_status_hashtags_and_pagination_concrete(): void
    {
        $apiJson = $this->apiJson();
        $schemas = $apiJson['components']['schemas'];

        $this->assertSame('integer', $schemas['ArticleStatus']['type']);
        $this->assertSame([0, 1, 2, 3, 4], $schemas['ArticleStatus']['enum']);
        $pendingResponseSchema = $apiJson['paths']['/articles/pending']['get']['responses']['200']['content']['application/json']['schema'];
        $itemProperties = $pendingResponseSchema['properties']['items']['items']['properties'];

        $this->assertSame('string', $itemProperties['uuid']['type']);
        $this->assertSame('string', $itemProperties['title_jp']['type']);
        $this->assertSame('integer', $itemProperties['status']['type']);
        $this->assertSame('string', $itemProperties['status_label']['type']);
        $this->assertSame(
            '#/components/schemas/HashtagResource',
            $itemProperties['hashtags']['items']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/PaginationResource',
            $pendingResponseSchema['properties']['pagination']['$ref'],
        );
        $this->assertSame('integer', $schemas['PaginationResource']['properties']['page']['type']);
        $this->assertSame('boolean', $schemas['PaginationResource']['properties']['has_more']['type']);
    }
}
