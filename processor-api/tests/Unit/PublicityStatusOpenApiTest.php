<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * `publicity` is a backend enum on the public contract. The article and catalogue Resources must
 * reference one reusable `PublicityStatus` component, so Orval generates a shared frontend enum
 * and the client keeps no hand-written copy of the 0/1 values (issue #497).
 */
class PublicityStatusOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function schemas(): array
    {
        $apiJson = json_decode(
            file_get_contents(__DIR__.'/../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $apiJson['components']['schemas'];
    }

    public function test_publicity_status_is_a_reusable_integer_enum(): void
    {
        $schema = $this->schemas()['PublicityStatus'];

        $this->assertSame('integer', $schema['type']);
        $this->assertSame([0, 1], $schema['enum']);
    }

    public function test_resources_reference_the_publicity_status_component(): void
    {
        $schemas = $this->schemas();

        foreach ([
            'ArticleResource',
            'ArticleDetailResource',
            'CatalogueResource',
            'CatalogueDetailResource',
        ] as $resource) {
            $this->assertSame(
                '#/components/schemas/PublicityStatus',
                $schemas[$resource]['properties']['publicity']['$ref'] ?? null,
                "{$resource}.publicity must reference PublicityStatus",
            );
            $this->assertContains('publicity', $schemas[$resource]['required'], "{$resource}.publicity must be required");
        }
    }
}
