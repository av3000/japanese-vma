<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use PHPUnit\Framework\TestCase;

/**
 * The generated client types `messages` as `Record<string, string>` and the locale list as an
 * array of named items, because of what these components say (#660, AC-5).
 */
class TranslationsOpenApiTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function api(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../../../api.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    public function test_both_endpoints_are_documented_and_reference_named_components(): void
    {
        $paths = $this->api()['paths'];

        $this->assertSame(
            '#/components/schemas/TranslationLocaleListResource',
            $paths['/translations/locales']['get']['responses']['200']['content']['application/json']['schema']['$ref'] ?? null,
        );
        $this->assertSame(
            '#/components/schemas/TranslationBundleResource',
            $paths['/translations/{locale}']['get']['responses']['200']['content']['application/json']['schema']['$ref'] ?? null,
        );
    }

    public function test_the_bundle_documents_the_typed_404(): void
    {
        $notFound = $this->api()['paths']['/translations/{locale}']['get']['responses']['404'] ?? null;

        $this->assertNotNull($notFound, 'The unknown or inactive locale 404 must be in api.json.');
        $this->assertSame('Locale not found', $notFound['description']);
        $this->assertSame(
            '#/components/schemas/ProblemDetailsResource',
            $notFound['content']['application/json']['schema']['$ref'] ?? null,
        );
    }

    public function test_bundle_messages_is_a_string_to_string_map(): void
    {
        $bundle = $this->api()['components']['schemas']['TranslationBundleResource'];

        $this->assertSame(['locale', 'fallback_locale', 'messages'], $bundle['required']);
        $this->assertSame('object', $bundle['properties']['messages']['type']);
        $this->assertSame(['type' => 'string'], $bundle['properties']['messages']['additionalProperties']);
    }

    public function test_locale_items_carry_code_name_and_the_fallback_flag(): void
    {
        $schemas = $this->api()['components']['schemas'];

        $this->assertSame(
            '#/components/schemas/TranslationLocaleResource',
            $schemas['TranslationLocaleListResource']['properties']['items']['items']['$ref'] ?? null,
        );

        $locale = $schemas['TranslationLocaleResource'];

        $this->assertSame('string', $locale['properties']['code']['type']);
        $this->assertSame('string', $locale['properties']['name']['type']);
        $this->assertSame('boolean', $locale['properties']['is_fallback']['type']);
        $this->assertSame(['code', 'name', 'is_fallback'], $locale['required']);
    }
}
