<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class LikeErrors
{
    /**
     * One 404 for "no such target" and for "target you may not see".
     *
     * The detail deliberately names neither the reason nor the target, because a
     * distinguishable response would let an unauthenticated caller enumerate private
     * articles and catalogues by their sequential ids.
     */
    public static function targetNotFound(): ResultError
    {
        return new ResultError(
            code: 'Likes.TargetNotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Like target not found',
            detail: 'The requested target does not exist or is not available to you'
        );
    }
}
