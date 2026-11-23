<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Infrastructure\Persistence\Models\Uniquehashtag;

/**
 * Creates a tag row the way production writes do: content and normalised key from HashtagName.
 * The model has no saving hook, so a bare Uniquehashtag::create() would leave the key empty.
 */
trait CreatesHashtags
{
    protected function hashtag(string $content): Uniquehashtag
    {
        $name = HashtagName::from($content);

        return Uniquehashtag::query()->create(['content' => $name->display, 'normalized_content' => $name->key]);
    }
}
