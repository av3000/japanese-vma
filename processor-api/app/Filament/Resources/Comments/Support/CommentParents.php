<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments\Support;

use App\Application\Engagement\Interfaces\Readers\EntityReferenceReaderInterface;
use App\Domain\Shared\DTOs\EntityReferenceDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Comment;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use WeakMap;

/**
 * Parent entity labels and links for a page of comments. The first column that asks resolves the
 * whole page in one reader call (one query per entity type present); the result is kept per page
 * of records, so rendering N rows never costs N queries.
 */
final class CommentParents
{
    /** @var WeakMap<object, array<string, EntityReferenceDTO>>|null */
    private static ?WeakMap $pages = null;

    /**
     * @param Collection<int, Comment>|Paginator|iterable<Comment>|null $page the records on screen
     */
    public static function of(Comment $comment, mixed $page = null): ?EntityReferenceDTO
    {
        $type = ObjectTemplateType::tryFromLegacyValue((int) $comment->template_id);

        if ($type === null) {
            return null;
        }

        $key = EntityReferenceDTO::key($type, (int) $comment->real_object_id);

        if (is_object($page)) {
            self::$pages ??= new WeakMap();
            self::$pages[$page] ??= self::resolve($page instanceof Paginator ? $page->items() : $page);

            return self::$pages[$page][$key] ?? EntityReferenceDTO::missing($type, (int) $comment->real_object_id);
        }

        return self::resolve([$comment])[$key] ?? EntityReferenceDTO::missing($type, (int) $comment->real_object_id);
    }

    /**
     * @param iterable<Comment> $comments
     *
     * @return array<string, EntityReferenceDTO>
     */
    private static function resolve(iterable $comments): array
    {
        $refs = [];

        foreach ($comments as $comment) {
            $type = ObjectTemplateType::tryFromLegacyValue((int) $comment->template_id);

            if ($type !== null) {
                $refs[] = [$type, (int) $comment->real_object_id];
            }
        }

        return $refs === [] ? [] : app(EntityReferenceReaderInterface::class)->find($refs);
    }
}
