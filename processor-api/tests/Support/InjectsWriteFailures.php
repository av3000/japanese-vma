<?php

declare(strict_types=1);

namespace Tests\Support;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Rebinds a container entry to a stand-in that forwards every call to the real binding except one
 * method, which throws. Lets a test fail the write that follows an activity row and check that
 * the row rolls back with it.
 *
 * Resolve the service under test after calling failOn(), so it receives the stand-in.
 *
 * @mixin \Tests\TestCase
 */
trait InjectsWriteFailures
{
    /**
     * @param class-string $abstract an interface or a non-final class
     */
    protected function failOn(string $abstract, string $failingMethod): void
    {
        $real = $this->app->make($abstract);
        $double = $this->createMock($abstract);

        foreach ((new ReflectionClass($abstract))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isStatic()) {
                continue;
            }

            $name = $method->getName();

            $double->method($name)->willReturnCallback($name === $failingMethod
                ? static fn (): never => throw new RuntimeException("Simulated {$name} failure")
                : static fn (mixed ...$arguments): mixed => $real->{$name}(...$arguments));
        }

        $this->app->instance($abstract, $double);
    }
}
