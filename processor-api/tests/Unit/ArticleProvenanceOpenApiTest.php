<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ArticleProvenanceOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function schemas(): array
    {
        return json_decode(
            (string) file_get_contents(__DIR__.'/../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        )['components']['schemas'];
    }

    public function test_article_origin_is_a_reusable_enum_schema(): void
    {
        $origin = $this->schemas()['ArticleOrigin'];

        $this->assertSame('string', $origin['type']);
        $this->assertSame(['user', 'imported'], $origin['enum']);
    }

    public function test_list_and_detail_articles_carry_a_required_origin_and_a_nullable_source(): void
    {
        $schemas = $this->schemas();

        foreach (['ArticleResource', 'ArticleDetailResource'] as $name) {
            $properties = $schemas[$name]['properties'];

            $this->assertSame('#/components/schemas/ArticleOrigin', $properties['origin']['$ref'], $name);
            $this->assertContains('origin', $schemas[$name]['required'], $name);
            $this->assertSame(
                [['$ref' => '#/components/schemas/ArticleSourceResource'], ['type' => 'null']],
                $properties['source']['anyOf'],
                $name,
            );
        }

        $this->assertSame(['key', 'name', 'homepage_url'], $schemas['ArticleSourceResource']['required']);
    }
}
