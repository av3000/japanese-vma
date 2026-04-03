<?php

namespace Tests\Feature\Documentation;

use Tests\TestCase;

class ScrambleOpenApiSchemaTest extends TestCase
{
    public function test_article_and_auth_endpoints_are_not_documented_as_string_responses(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonPath('paths./me.get.responses.200.content.application/json.schema.type', 'object')
            ->assertJsonPath('paths./catalogues.get.responses.200.content.application/json.schema.type', 'object')
            ->assertJsonPath('paths./catalogues/{uuid}.get.responses.200.content.application/json.schema.type', 'object');
    }

    public function test_article_nested_resources_are_exposed_as_named_openapi_components(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonPath('paths./articles.get.responses.200.content.application/json.schema.$ref', '#/components/schemas/ArticleListResource')
            ->assertJsonPath('paths./articles.post.responses.201.content.application/json.schema.$ref', '#/components/schemas/UuidCreatedResource')
            ->assertJsonPath('paths./articles/{uid}.get.responses.200.content.application/json.schema.$ref', '#/components/schemas/ArticleDetailResource')
            ->assertJsonPath('paths./articles/{uid}.put.responses.200.content.application/json.schema.$ref', '#/components/schemas/ArticleResource')
            ->assertJsonPath('components.schemas.ArticleListResource.properties.pagination.$ref', '#/components/schemas/PaginationResource')
            ->assertJsonPath('components.schemas.ArticleResource.properties.author.$ref', '#/components/schemas/ArticleAuthorResource')
            ->assertJsonPath('components.schemas.ArticleResource.properties.hashtags.items.$ref', '#/components/schemas/HashtagResource')
            ->assertJsonPath('components.schemas.ArticleResource.properties.engagement.$ref', '#/components/schemas/ArticleListEngagementResource')
            ->assertJsonPath('components.schemas.ArticleResource.properties.processing_status.anyOf.0.$ref', '#/components/schemas/ProcessingStatusResource')
            ->assertJsonPath('components.schemas.ArticleDetailResource.properties.article.properties.author.$ref', '#/components/schemas/ArticleAuthorResource')
            ->assertJsonPath('components.schemas.ArticleDetailResource.properties.article.properties.hashtags.items.$ref', '#/components/schemas/HashtagResource')
            ->assertJsonPath('components.schemas.ArticleDetailResource.properties.article.properties.engagement.anyOf.0.$ref', '#/components/schemas/ArticleDetailEngagementResource')
            ->assertJsonPath('components.schemas.ArticleDetailResource.properties.article.properties.processing_status.anyOf.0.$ref', '#/components/schemas/ProcessingStatusResource');
    }
}
