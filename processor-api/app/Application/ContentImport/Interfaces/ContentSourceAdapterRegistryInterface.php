<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces;

interface ContentSourceAdapterRegistryInterface
{
    public function for(string $sourceKey): ?ContentSourceAdapterInterface;
}
