<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Resources;

use App\Domain\Community\Posts\Models\Post as DomainPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The moderation response: identity plus the resulting state, and nothing else.
 *
 * Locking is orthogonal to the Post's content, and the admin performing it does
 * not need the body back. Returning the full detail shape here would make the
 * generated client's lock call look like a read.
 *
 * @property-read DomainPost $resource
 */
class PostLockResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{uuid: string, locked: bool}
     */
    public function toArray(Request $request): array
    {
        /** @var DomainPost $post */
        $post = $this->resource;

        return [
            'uuid' => (string) $post->getUuid()->value(),
            'locked' => (bool) $post->isLocked(),
        ];
    }
}
