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

    /**
     * The moderation responses must reference named components rather than inline
     * shapes. An inline schema is generated from the controller attribute alone, so
     * it silently stops matching the Resource it claims to describe.
     */
    public function test_moderation_responses_reference_named_component_schemas(): void
    {
        $apiJson = $this->apiJson();

        $this->assertSame(
            '#/components/schemas/ArticleModerationListResource',
            $apiJson['paths']['/articles/pending']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/ArticleStatusResource',
            $apiJson['paths']['/articles/{uuid}/status']['post']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    public function test_admin_only_moderation_endpoints_document_401_and_403(): void
    {
        $apiJson = $this->apiJson();

        foreach ([['/articles/pending', 'get'], ['/articles/{uuid}/status', 'post']] as [$path, $method]) {
            $responses = $apiJson['paths'][$path][$method]['responses'];

            $this->assertSame(
                '#/components/responses/AuthenticationException',
                $responses['401']['$ref'],
                "{$method} {$path} must document 401",
            );
            $this->assertSame(
                '#/components/responses/AuthorizationException',
                $responses['403']['$ref'],
                "{$method} {$path} must document 403",
            );
        }
    }

    public function test_moderation_schemas_keep_status_hashtags_and_pagination_concrete(): void
    {
        $apiJson = $this->apiJson();
        $schemas = $apiJson['components']['schemas'];

        $this->assertSame('integer', $schemas['ArticleStatus']['type']);
        $this->assertSame([0, 1, 2, 3, 4], $schemas['ArticleStatus']['enum']);

        $listSchema = $schemas['ArticleModerationListResource'];

        $this->assertSame(
            '#/components/schemas/ArticleModerationItemResource',
            $listSchema['properties']['items']['items']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/PaginationResource',
            $listSchema['properties']['pagination']['$ref'],
        );

        $itemProperties = $schemas['ArticleModerationItemResource']['properties'];

        $this->assertSame(
            ['uuid', 'title_jp', 'status', 'status_label', 'hashtags', 'created_at'],
            array_keys($itemProperties),
        );
        $this->assertSame('string', $itemProperties['uuid']['type']);
        $this->assertSame('string', $itemProperties['title_jp']['type']);
        $this->assertSame('integer', $itemProperties['status']['type']);
        $this->assertSame('string', $itemProperties['status_label']['type']);
        $this->assertSame('string', $itemProperties['created_at']['type']);
        $this->assertSame(
            '#/components/schemas/HashtagResource',
            $itemProperties['hashtags']['items']['$ref'],
        );

        $this->assertSame('integer', $schemas['PaginationResource']['properties']['page']['type']);
        $this->assertSame('boolean', $schemas['PaginationResource']['properties']['has_more']['type']);
    }
}
