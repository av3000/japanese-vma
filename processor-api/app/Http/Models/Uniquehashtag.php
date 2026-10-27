<?php

namespace App\Http\Models;

use App\Domain\Engagement\ValueObjects\HashtagName;
use Illuminate\Database\Eloquent\Model;

class Uniquehashtag extends Model
{
    /**
     * Legacy hashtag helpers (app/Helpers/hashtags-methods.php, UpdateArticleHashtagsAction) still
     * create tags through this model; keep the comparison key the admin panel's duplicate finder
     * relies on in step with them. Goes away with those helpers (#149, #152).
     */
    protected static function booted(): void
    {
        static::saving(static function (Uniquehashtag $tag): void {
            $tag->normalized_content = HashtagName::keyFor((string) $tag->content);
        });
    }
}
