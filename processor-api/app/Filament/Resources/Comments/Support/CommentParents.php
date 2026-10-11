<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments\Support;

use App\Application\Comments\Interfaces\Readers\CommentParentReaderInterface;
use App\Domain\Comments\DTOs\CommentParentDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Support\ClientUrls;
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
    /** @var WeakMap<object, array<string, CommentParentDTO>>|null */
    private static ?WeakMap $pages = null;

    /**
     * @param Collection<int, Comment>|Paginator|iterable<Comment>|null $page the records on screen
     */
    public static function of(Comment $comment, mixed $page = null): ?CommentParentDTO
    {
        $type = ObjectTemplateType::tryFromLegacyValue((int) $comment->template_id);

        if ($type === null) {
            return null;
        }

        $key = CommentParentDTO::key($type, (int) $comment->real_object_id);

        if (is_object($page)) {
            self::$pages ??= new WeakMap();
            self::$pages[$page] ??= self::resolve($page instanceof Paginator ? $page->items() : $page);

            return self::$pages[$page][$key] ?? CommentParentDTO::missing($type, (int) $comment->real_object_id);
        }

        return self::resolve([$comment])[$key] ?? CommentParentDTO::missing($type, (int) $comment->real_object_id);
    }

    public static function label(?CommentParentDTO $parent): string
    {
        if ($parent === null) {
            return 'Unknown entity';
        }

        return $parent->exists()
            ? mb_strimwidth((string) $parent->label, 0, 60, '…')
            : 'Deleted '.mb_strtolower($parent->type->label());
    }

    /**
     * The admin page when this panel has one for the type and the viewer may open it, otherwise
     * the public client page.
     */
    public static function url(?CommentParentDTO $parent): ?string
    {
        if ($parent === null || ! $parent->exists()) {
            return null;
        }

        $resource = match ($parent->type) {
            ObjectTemplateType::ARTICLE => ArticleResource::class,
            ObjectTemplateType::POST => PostResource::class,
            default => null,
        };

        if ($resource !== null && $resource::canViewAny()) {
            return $resource::getUrl('view', ['record' => $parent->uuid]);
        }

        return ClientUrls::forEntity($parent->type, $parent->uuid);
    }

    /**
     * @param iterable<Comment> $comments
     *
     * @return array<string, CommentParentDTO>
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

        return $refs === [] ? [] : app(CommentParentReaderInterface::class)->findParents($refs);
    }
}
