<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Sentences;

use PHPUnit\Framework\TestCase;

class SentenceAuthoringOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function apiJson(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Canary, not a requirement. Scramble documents an inferred `200` beside the
     * annotated `201` on create, and Orval turns the pair into
     * `string | SentenceResource`. `client/src/api/sentences/authoring.ts` narrows
     * that at runtime.
     *
     * When this test starts failing, Scramble stopped emitting the artifact:
     * delete this test and the `asSentenceResource` guard in the client seam.
     *
     * Ruled out as fixes: widening the action return type to `JsonResponse|JsonResource`,
     * `response()->json(…, 201)`, and returning a bare resource. Scramble only replaces
     * an inferred response when the attribute declares the same status, and the runtime
     * 201 is asserted by SentenceAuthoringV1Test so it cannot move.
     *
     * The same artifact affects `POST /comments`, `POST /register`,
     * `POST /catalogues/{uuid}/items` and `DELETE /catalogues/{uuid}/items/{itemId}`.
     */
    public function test_sentence_create_still_documents_the_spurious_200_artifact(): void
    {
        $responses = $this->apiJson()['paths']['/sentences']['post']['responses'];

        $successCodes = array_map(strval(...), array_values(array_filter(
            array_keys($responses),
            static fn ($code): bool => str_starts_with((string) $code, '2'),
        )));

        sort($successCodes);

        $this->assertSame(['200', '201'], $successCodes);
    }

    /**
     * Detail, create and update all return the same `SentenceResource`. Inline
     * response shapes are generated from the controller annotation alone, so they
     * silently stop matching the Resource they claim to describe.
     */
    public function test_sentence_read_and_write_responses_reference_the_shared_component(): void
    {
        $apiJson = $this->apiJson();

        $expected = '#/components/schemas/SentenceResource';

        $this->assertSame(
            $expected,
            $apiJson['paths']['/sentences/{identifier}']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            $expected,
            $apiJson['paths']['/sentences']['post']['responses']['201']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            $expected,
            $apiJson['paths']['/sentences/{uuid}']['put']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    /**
     * `id` and `user_id` are integers at runtime. When Scramble cannot resolve
     * `$this->resource` it degrades every property to `string`, which reaches the
     * frontend as a wrong-but-compiling type.
     */
    public function test_sentence_resource_component_keeps_runtime_scalar_types(): void
    {
        $properties = $this->apiJson()['components']['schemas']['SentenceResource']['properties'];

        $this->assertSame('integer', $properties['id']['type']);
        $this->assertSame(['integer', 'null'], $properties['user_id']['type']);
        $this->assertSame(['string', 'null'], $properties['tatoeba_entry']['type']);
        $this->assertSame('string', $properties['content']['type']);
    }

    /**
     * `kanjis` is the list the sentence UI renders, so it has to carry a typed item
     * schema or the generated client types it `unknown[]`.
     *
     * `words` stays untyped: it is built with `array_map` rather than
     * `WordResource::collection()`, because `mapInto` passes the collection key as
     * WordResource's second constructor argument (typed `?ViewerCatalogueStateDTO`)
     * and throws on any non-empty list. No client reads `words` today.
     */
    public function test_sentence_resource_component_types_the_kanji_items_the_ui_renders(): void
    {
        $properties = $this->apiJson()['components']['schemas']['SentenceResource']['properties'];

        $this->assertSame('#/components/schemas/KanjiResource', $properties['kanjis']['items']['$ref']);
        $this->assertSame([], $properties['words']['items']);
    }

    public function test_sentence_write_endpoints_document_their_failure_responses(): void
    {
        $paths = $this->apiJson()['paths'];

        foreach ([['/sentences', 'post'], ['/sentences/{uuid}', 'put']] as [$path, $method]) {
            $responses = $paths[$path][$method]['responses'];

            $this->assertArrayHasKey('401', $responses, "{$method} {$path} must document 401");
            $this->assertArrayHasKey('403', $responses, "{$method} {$path} must document 403");
            $this->assertArrayHasKey('422', $responses, "{$method} {$path} must document 422");
        }

        $this->assertArrayHasKey('401', $paths['/sentences/{uuid}']['delete']['responses']);
    }
}
