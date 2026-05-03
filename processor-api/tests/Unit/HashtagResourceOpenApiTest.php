<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HashtagResourceOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function apiJson(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    public function test_hashtag_resource_schema_has_concrete_field_types(): void
    {
        $apiJson = $this->apiJson();

        $properties = $apiJson['components']['schemas']['HashtagResource']['properties'];

        $this->assertSame('integer', $properties['id']['type']);
        $this->assertSame('string', $properties['content']['type']);
    }

    public function test_article_and_catalogue_detail_schemas_are_flat(): void
    {
        $apiJson = $this->apiJson();

        $articleProperties = $apiJson['components']['schemas']['ArticleDetailResource']['properties'];
        $catalogueProperties = $apiJson['components']['schemas']['CatalogueDetailResource']['properties'];

        $this->assertArrayHasKey('uid', $articleProperties);
        $this->assertArrayNotHasKey('article', $articleProperties);
        $this->assertArrayHasKey('uuid', $catalogueProperties);
        $this->assertArrayNotHasKey('catalogue', $catalogueProperties);
    }

    public function test_store_comment_request_reuses_object_template_type_schema(): void
    {
        $apiJson = $this->apiJson();

        $objectTemplateTypeSchema = $apiJson['components']['schemas']['ObjectTemplateType'];
        $storeCommentRequestProperties = $apiJson['components']['schemas']['StoreCommentRequest']['properties'];
        $storeCommentRequestRequired = $apiJson['components']['schemas']['StoreCommentRequest']['required'];

        $this->assertSame('string', $objectTemplateTypeSchema['type']);
        $this->assertContains('ad69baf6-1a1f-42bd-8176-74ab5fbd69bd', $objectTemplateTypeSchema['enum']);
        $this->assertSame(
            '#/components/schemas/ObjectTemplateType',
            $storeCommentRequestProperties['entity_type']['$ref']
        );
        $this->assertSame('integer', $storeCommentRequestProperties['entity_id']['type']);
        $this->assertContains('entity_type', $storeCommentRequestRequired);
        $this->assertContains('entity_id', $storeCommentRequestRequired);
        $this->assertContains('entity_uuid', $storeCommentRequestRequired);
    }
}
