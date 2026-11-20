<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Application\Translations\Services\TranslationEditorService;
use App\Application\Translations\Services\TranslationService;
use App\Domain\Translations\Services\TranslationResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * The override rule has one home (#661, AC-7): the read service (HTTP bundle) and the edit service
 * (Filament) both take the resolver, so a bundle and a completion count cannot disagree about
 * what counts as translated.
 */
class ResolverBoundaryTest extends TestCase
{
    public function test_the_read_service_and_the_edit_service_both_depend_on_the_resolver(): void
    {
        foreach ([TranslationService::class, TranslationEditorService::class] as $service) {
            $this->assertContains(
                TranslationResolver::class,
                $this->constructorTypes($service),
                "{$service} must take its merge, state and completion answers from the resolver.",
            );
        }
    }

    public function test_the_resolver_has_no_collaborators_so_it_cannot_touch_io(): void
    {
        $constructor = (new ReflectionClass(TranslationResolver::class))->getConstructor();

        $this->assertTrue($constructor === null || $constructor->getNumberOfParameters() === 0);
    }

    /** @return list<string> */
    private function constructorTypes(string $class): array
    {
        $types = [];

        foreach ((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType) {
                $types[] = $type->getName();
            }
        }

        return $types;
    }
}
