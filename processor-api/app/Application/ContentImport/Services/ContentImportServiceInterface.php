<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Services;

use App\Domain\ContentImport\DTOs\ImportRunResult;
use App\Shared\Results\Result;

interface ContentImportServiceInterface
{
    /**
     * Run one import against one Content Source. A run whose source could not be read still
     * succeeds as a Result: its ImportRunResult has status `failed` and is recorded.
     *
     * Success data: ImportRunResult. Failure: unknown or disabled source, missing system author,
     * or another run of the same source still in progress (ContentImport.RunInProgress).
     */
    public function run(string $sourceKey, bool $dryRun = false): Result;

    /**
     * @return list<string>
     */
    public function enabledSourceKeys(): array;
}
