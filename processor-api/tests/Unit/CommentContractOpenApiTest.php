<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The Comment contract as generated clients see it.
 *
 * Every assertion here exists because the previous shape produced a client that
 * could not be written correctly: two enums for one concept, an untyped failure
 * body, and a `replies` array that the schema promised and the API never filled.
 */
class CommentContractOpenApiTest extends TestCase
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

    public function test_comment_read_paths_are_documented(): void
    {
        $paths = $this->apiJson()['paths'];

        foreach (['/articles', '/catalogues', '/posts', '/sentences'] as $parent) {
            self::assertArrayHasKey($parent.'/{uuid}/comments', $paths);
        }

        self::assertArrayHasKey('/comments/{uuid}/replies', $paths);
    }

    /**
     * Inline response shapes drift away from the Resource that claims to
     * describe them, and Orval generates anonymous types from them.
     */
    public function test_comment_responses_reference_named_component_schemas(): void
    {
        $paths = $this->apiJson()['paths'];

        self::assertSame(
            '#/components/schemas/CommentListResource',
            $paths['/articles/{uuid}/comments']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
        self::assertSame(
            '#/components/schemas/CommentReplyListResource',
            $paths['/comments/{uuid}/replies']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    /**
     * The create request takes `entity_type` as an ObjectTemplateType uuid. The
     * response used to answer with the title ("article"), so Orval generated a
     * second enum for the same concept and no client could round-trip a value.
     */
    public function test_the_entity_type_vocabulary_is_shared_between_request_and_response(): void
    {
        $schemas = $this->apiJson()['components']['schemas'];
        $comment = $schemas['CommentResource']['properties'];

        self::assertArrayNotHasKey('entity_type', $comment);
        self::assertSame(
            '#/components/schemas/ObjectTemplateType',
            $schemas['StoreCommentRequest']['properties']['entity_type']['$ref'],
        );
        self::assertSame(
            $schemas['ObjectTemplateType']['enum'],
            $comment['entity_type_uuid']['enum'] ?? null,
            'entity_type_uuid must offer exactly the ObjectTemplateType vocabulary.',
        );
    }

    public function test_the_comment_shape_carries_an_author_object_and_a_viewer_block(): void
    {
        $schemas = $this->apiJson()['components']['schemas'];
        $comment = $schemas['CommentResource']['properties'];

        self::assertArrayNotHasKey('author_id', $comment);
        self::assertArrayNotHasKey('author_name', $comment);
        self::assertArrayNotHasKey('is_liked_by_viewer', $comment);

        self::assertArrayHasKey('author', $comment);
        self::assertArrayHasKey('viewer', $comment);

        $viewer = $schemas['CommentViewerResource']['properties'];

        foreach (['is_liked', 'can_edit', 'can_delete'] as $field) {
            self::assertArrayHasKey($field, $viewer);
            self::assertSame('boolean', $viewer[$field]['type']);
        }
    }

    /**
     * A reply carries no replies of its own. Documenting the two the same way
     * would promise a nested array that is always empty and would generate a
     * self-referential TypeScript type.
     */
    public function test_replies_are_a_distinct_non_recursive_schema(): void
    {
        $schemas = $this->apiJson()['components']['schemas'];

        self::assertArrayHasKey('CommentReplyResource', $schemas);
        self::assertArrayNotHasKey('replies', $schemas['CommentReplyResource']['properties']);
        self::assertArrayNotHasKey('replies_count', $schemas['CommentReplyResource']['properties']);

        self::assertSame(
            '#/components/schemas/CommentReplyResource',
            $schemas['CommentResource']['properties']['replies']['items']['$ref'],
        );
        self::assertSame('integer', $schemas['CommentResource']['properties']['replies_count']['type']);
    }

    public function test_the_thread_query_contract_is_typed_and_closed(): void
    {
        $parameters = $this->apiJson()['paths']['/articles/{uuid}/comments']['get']['parameters'];
        $byName = array_column($parameters, null, 'name');

        self::assertSame(
            ['uuid', 'page', 'per_page', 'include_replies', 'replies_limit', 'sort_by', 'sort_dir'],
            array_column($parameters, 'name'),
        );
        self::assertSame(0, $byName['replies_limit']['schema']['minimum']);
        self::assertSame(50, $byName['replies_limit']['schema']['maximum']);
        self::assertSame(['created_at', 'updated_at'], $byName['sort_by']['schema']['enum']);
        self::assertSame(['asc', 'desc'], $byName['sort_dir']['schema']['enum']);
    }

    /**
     * TypedResults::fromError has always emitted problem details. Documenting
     * them as `{"type": "string"}` is why client/src/api/writeFailure.ts parses
     * an undocumented body by hand.
     */
    public function test_comment_failures_are_documented_as_problem_details(): void
    {
        $paths = $this->apiJson()['paths'];

        $cases = [
            ['/comments', 'post', '404'],
            ['/comments', 'post', '409'],
            ['/comments/{uuid}', 'put', '403'],
            ['/comments/{uuid}', 'put', '404'],
            ['/comments/{uuid}', 'delete', '403'],
            ['/comments/{uuid}', 'delete', '404'],
            ['/comments/{uuid}/replies', 'get', '404'],
        ];

        foreach ($cases as [$path, $method, $status]) {
            self::assertSame(
                '#/components/schemas/ProblemDetailsResource',
                $paths[$path][$method]['responses'][$status]['content']['application/json']['schema']['$ref'],
                "{$method} {$path} {$status} must reference ProblemDetailsResource.",
            );
        }

        $problem = $this->apiJson()['components']['schemas']['ProblemDetailsResource']['properties'];

        foreach (['type', 'title', 'status', 'detail', 'instance', 'timestamp'] as $field) {
            self::assertArrayHasKey($field, $problem);
        }
    }
}
