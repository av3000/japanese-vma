<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PostReadsOpenApiTest extends TestCase
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

    public function test_public_post_read_paths_are_documented(): void
    {
        $paths = $this->apiJson()['paths'];

        self::assertArrayHasKey('/posts', $paths);
        self::assertArrayHasKey('/posts/{identifier}', $paths);

        $parameter = $paths['/posts/{identifier}']['get']['parameters'][0];

        self::assertSame('identifier', $parameter['name']);
        self::assertSame('path', $parameter['in']);
        self::assertSame('string', $parameter['schema']['type']);
    }

    /**
     * Inline response shapes drift away from the Resource that claims to
     * describe them, and Orval generates anonymous types from them.
     */
    public function test_post_read_responses_reference_named_component_schemas(): void
    {
        $paths = $this->apiJson()['paths'];

        self::assertSame(
            '#/components/schemas/PostListResource',
            $paths['/posts']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
        self::assertSame(
            '#/components/schemas/PostDetailResource',
            $paths['/posts/{identifier}']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    public function test_the_list_query_contract_is_typed_and_closed(): void
    {
        $parameters = $this->apiJson()['paths']['/posts']['get']['parameters'];
        $byName = array_column($parameters, null, 'name');

        self::assertSame(
            ['page', 'per_page', 'keyword', 'hashtag', 'topic', 'sort'],
            array_column($parameters, 'name'),
        );

        self::assertSame('#/components/schemas/PostTopic', $byName['topic']['schema']['$ref']);
        self::assertSame('#/components/schemas/PostSort', $byName['sort']['schema']['$ref']);
    }

    public function test_topic_and_sort_are_reusable_enum_schemas(): void
    {
        $schemas = $this->apiJson()['components']['schemas'];

        self::assertSame('integer', $schemas['PostTopic']['type']);
        self::assertSame([1, 2, 3, 4, 5, 6, 7], $schemas['PostTopic']['enum']);

        self::assertSame('string', $schemas['PostSort']['type']);
        self::assertSame(['newest', 'popular'], $schemas['PostSort']['enum']);
    }

    /**
     * List items must not advertise `content`; only the detail response carries it.
     */
    public function test_list_items_and_detail_are_separate_honest_schemas(): void
    {
        $schemas = $this->apiJson()['components']['schemas'];

        self::assertSame(
            '#/components/schemas/PostListItemResource',
            $schemas['PostListResource']['properties']['items']['items']['$ref'],
        );

        self::assertArrayNotHasKey('content', $schemas['PostListItemResource']['properties']);
        self::assertArrayHasKey('content', $schemas['PostDetailResource']['properties']);
        self::assertContains('content', $schemas['PostDetailResource']['required']);

        foreach (['id', 'uuid', 'entity_type_uuid', 'title', 'topic', 'topic_label', 'locked', 'author', 'hashtags', 'engagement', 'created_at', 'updated_at'] as $field) {
            self::assertContains($field, $schemas['PostListItemResource']['required'], "list item must expose {$field}");
            self::assertContains($field, $schemas['PostDetailResource']['required'], "detail must expose {$field}");
        }
    }

    public function test_pagination_metadata_is_concrete(): void
    {
        $schemas = $this->apiJson()['components']['schemas'];
        $pagination = $schemas['PostListResource']['properties']['pagination'];

        $paginationSchema = isset($pagination['$ref'])
            ? $schemas[basename($pagination['$ref'])]
            : $pagination;

        foreach (['page', 'per_page', 'total', 'last_page', 'has_more'] as $field) {
            self::assertArrayHasKey($field, $paginationSchema['properties']);
        }
    }
}
