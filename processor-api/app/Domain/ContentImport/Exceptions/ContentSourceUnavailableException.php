<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\Exceptions;

use RuntimeException;

/**
 * An adapter could not read its source as a whole: the network failed, robots.txt disallows the
 * path, or the response no longer has the shape the adapter understands. The run fails loudly
 * instead of importing a partial or garbled listing.
 */
class ContentSourceUnavailableException extends RuntimeException
{
}
