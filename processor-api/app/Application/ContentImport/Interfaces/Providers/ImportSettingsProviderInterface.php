<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Interfaces\Providers;

use App\Domain\ContentImport\ValueObjects\ImportRunSettings;

interface ImportSettingsProviderInterface
{
    /**
     * The settings an Import Run of this Content Source applies.
     */
    public function for(string $sourceKey): ImportRunSettings;
}
