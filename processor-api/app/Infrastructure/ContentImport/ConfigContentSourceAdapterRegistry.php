<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport;

use App\Application\ContentImport\Interfaces\ContentSourceAdapterInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterRegistryInterface;
use Illuminate\Contracts\Container\Container;
use LogicException;

/**
 * Adapters are listed in `content_import.sources.<key>.adapter` and resolved from the container,
 * so a test can swap a fake in by rebinding the class.
 */
class ConfigContentSourceAdapterRegistry implements ContentSourceAdapterRegistryInterface
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function for(string $sourceKey): ?ContentSourceAdapterInterface
    {
        $class = config("content_import.sources.{$sourceKey}.adapter");

        if (! is_string($class) || $class === '') {
            return null;
        }

        $adapter = $this->container->make($class);

        if (! $adapter instanceof ContentSourceAdapterInterface || $adapter->key() !== $sourceKey) {
            throw new LogicException("Adapter {$class} is not a content source adapter for {$sourceKey}");
        }

        return $adapter;
    }
}
