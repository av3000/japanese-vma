<?php

namespace App\Domain\Pdf\DTOs;

/**
 * A catalogue PDF view model: the data one Blade needs, and nothing else.
 *
 * The export pipeline is shared across kinds - lookup, policy, download accounting and
 * error handling are identical - but the payloads are not, so each kind gets its own
 * implementation rather than one array carrying every kind's keys with all but one empty.
 */
interface CataloguePdfViewDataInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array;
}
