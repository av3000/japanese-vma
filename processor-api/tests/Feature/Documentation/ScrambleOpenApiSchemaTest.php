<?php

namespace Tests\Feature\Documentation;

use Tests\TestCase;

class ScrambleOpenApiSchemaTest extends TestCase
{
    public function test_article_and_auth_endpoints_are_not_documented_as_string_responses(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonPath('paths./articles.get.responses.200.content.application/json.schema.type', 'object')
            ->assertJsonPath('paths./articles/{uid}.get.responses.200.content.application/json.schema.type', 'object')
            ->assertJsonPath('paths./me.get.responses.200.content.application/json.schema.type', 'object')
            ->assertJsonPath('paths./catalogues.get.responses.200.content.application/json.schema.type', 'object')
            ->assertJsonPath('paths./catalogues/{uuid}.get.responses.200.content.application/json.schema.type', 'object');
    }
}
