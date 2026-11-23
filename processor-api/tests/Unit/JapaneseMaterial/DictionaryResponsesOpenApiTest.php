<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial;

use PHPUnit\Framework\TestCase;

/**
 * The dictionary list and detail responses must reference their named Resource components.
 * Inline `#[Response(type: 'array{...}')]` shapes made Orval name the frontend types after the
 * status code (`KanjiIndex200ItemsItem`, `WordShow200`, ...) and were a second description of
 * each Resource, free to drift from it.
 */
class DictionaryResponsesOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function apiJson(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(string $component): array
    {
        return $this->apiJson()['components']['schemas'][$component];
    }

    private function responseRef(string $path): string
    {
        return $this->apiJson()['paths'][$path]['get']['responses']['200']['content']['application/json']['schema']['$ref'];
    }

    public function test_list_and_detail_responses_reference_named_components(): void
    {
        $this->assertSame('#/components/schemas/KanjiListResource', $this->responseRef('/kanjis'));
        $this->assertSame('#/components/schemas/WordListResource', $this->responseRef('/words'));
        $this->assertSame('#/components/schemas/SentenceListResource', $this->responseRef('/sentences'));
        $this->assertSame('#/components/schemas/RadicalListResource', $this->responseRef('/radicals'));
        $this->assertSame('#/components/schemas/WordDetailResource', $this->responseRef('/words/{identifier}'));
        $this->assertSame('#/components/schemas/RadicalResource', $this->responseRef('/radicals/{identifier}'));
    }

    public function test_list_items_reference_their_item_resource(): void
    {
        foreach ([
            'KanjiListResource' => 'KanjiResource',
            'WordListResource' => 'WordResource',
            'SentenceListResource' => 'SentenceResource',
            'RadicalListResource' => 'RadicalResource',
        ] as $list => $item) {
            $this->assertSame(
                "#/components/schemas/{$item}",
                $this->schema($list)['properties']['items']['items']['$ref'],
                "{$list}.items",
            );
        }
    }

    public function test_viewer_catalogue_state_is_one_shared_nullable_component(): void
    {
        $expected = [
            'anyOf' => [
                ['$ref' => '#/components/schemas/ViewerCatalogueStateResource'],
                ['type' => 'null'],
            ],
        ];

        $this->assertSame($expected, $this->schema('KanjiResource')['properties']['viewer_catalogue_state']);
        $this->assertSame($expected, $this->schema('WordResource')['properties']['viewer_catalogue_state']);

        $state = $this->schema('ViewerCatalogueStateResource')['properties'];
        $this->assertSame('boolean', $state['is_saved']['type']);
        $this->assertSame(['boolean', 'null'], $state['is_known']['type']);
    }

    /**
     * Without a class-level `@property` Scramble cannot resolve `$this->resource` and degrades
     * every property to `string`, which reaches the frontend as a wrong-but-compiling type.
     */
    public function test_word_and_radical_components_keep_runtime_scalar_types(): void
    {
        $word = $this->schema('WordResource')['properties'];
        $this->assertSame('integer', $word['id']['type']);
        $this->assertSame('array', $word['word_types']['type']);
        $this->assertSame(['string', 'null'], $word['jlpt']['type']);
        $this->assertSame('string', $word['word_type']['type']);

        $radical = $this->schema('RadicalResource')['properties'];
        $this->assertSame('integer', $radical['id']['type']);
        $this->assertSame(['integer', 'null'], $radical['strokes']['type']);
        $this->assertSame(['string', 'null'], $radical['meaning']['type']);
    }

    public function test_optional_includes_are_documented_as_optional(): void
    {
        $radical = $this->schema('RadicalResource');
        $this->assertNotContains('kanjis', $radical['required']);
        $this->assertSame('#/components/schemas/KanjiResource', $radical['properties']['kanjis']['items']['$ref']);

        // The sentence list reuses SentenceResource for its items and never sends the includes.
        $sentence = $this->schema('SentenceResource');
        $this->assertNotContains('kanjis', $sentence['required']);
        $this->assertNotContains('words', $sentence['required']);

        $wordDetail = $this->schema('WordDetailResource');
        $this->assertContains('word', $wordDetail['required']);
        $this->assertNotContains('kanjis', $wordDetail['required']);
        $this->assertNotContains('articles', $wordDetail['required']);
        $this->assertSame('#/components/schemas/KanjiResource', $wordDetail['properties']['kanjis']['items']['$ref']);
    }
}
