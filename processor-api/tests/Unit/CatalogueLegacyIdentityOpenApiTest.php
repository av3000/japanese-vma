<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CatalogueLegacyIdentityOpenApiTest extends TestCase
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

    public function test_resolver_is_documented_as_a_numeric_path_lookup(): void
    {
        $operation = $this->apiJson()['paths']['/catalogues/legacy/{id}']['get'];

        $this->assertSame('catalogue.resolveLegacyId', $operation['operationId']);

        $parameter = $operation['parameters'][0];

        $this->assertSame('id', $parameter['name']);
        $this->assertSame('path', $parameter['in']);
        $this->assertTrue($parameter['required']);
        $this->assertSame('integer', $parameter['schema']['type']);
    }

    /**
     * A FormRequest here made Scramble document a second, query-string `id`
     * alongside the path one, which orval turns into a required query argument
     * the caller has no reason to pass. The route constraint carries the
     * validation instead, so the operation must expose exactly one parameter.
     */
    public function test_resolver_documents_no_parameter_beyond_the_path_id(): void
    {
        $operation = $this->apiJson()['paths']['/catalogues/legacy/{id}']['get'];

        $this->assertCount(1, $operation['parameters']);
    }

    /**
     * An inline response schema is generated from the controller attribute
     * alone, so it silently stops matching the Resource it claims to describe.
     */
    public function test_resolver_response_references_a_named_component_schema(): void
    {
        $apiJson = $this->apiJson();

        $this->assertSame(
            '#/components/schemas/CatalogueLegacyIdentityResource',
            $apiJson['paths']['/catalogues/legacy/{id}']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    public function test_resolver_schema_stays_minimal(): void
    {
        $schema = $this->apiJson()['components']['schemas']['CatalogueLegacyIdentityResource'];

        $this->assertSame(['id', 'uuid'], array_keys($schema['properties']));
        $this->assertSame('integer', $schema['properties']['id']['type']);
        $this->assertSame('string', $schema['properties']['uuid']['type']);
        $this->assertSame(['id', 'uuid'], $schema['required']);
    }

    /**
     * The resolver sits in front of `catalogues/{uuid}` in the route file.
     * Guard the detail endpoint against being shadowed out of the document.
     */
    public function test_catalogue_detail_endpoint_is_still_documented(): void
    {
        $apiJson = $this->apiJson();

        $this->assertArrayHasKey('/catalogues/{uuid}', $apiJson['paths']);
        $this->assertSame(
            '#/components/schemas/CatalogueDetailResource',
            $apiJson['paths']['/catalogues/{uuid}']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }
}
