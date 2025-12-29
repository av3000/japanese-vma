<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\Queries;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\{KanjiCharacter, KanjiGrade, JlptLevel};
use App\Domain\Shared\ValueObjects\Pagination;

final readonly class KanjiQueryCriteria
{
    public function __construct(
        // TODO: pass in the persistence model instead of optional id parameters for each entity
        public readonly ?EntityId $articleId = null,
        public readonly ?int $customListId = null,
        public readonly ?EntityId $uuid = null,
        public readonly ?KanjiCharacter $character = null,
        public readonly ?KanjiGrade $grade = null,
        public readonly ?JlptLevel $jlpt = null,
        public readonly ?int $minStrokeCount = null,
        public readonly ?int $maxStrokeCount = null,
        public readonly ?array $meanings = null,
        public readonly ?array $onyomi = null,
        public readonly ?array $kunyomi = null,
        public readonly ?string $radical = null,
        public readonly ?Pagination $pagination = null,
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {}

    public static function forListing(
        int $perPage = 10,
        int $page = 1,
        ?string $character = null,
        ?string $grade = null,
        ?string $jlpt = null,
        ?int $minStrokeCount = null,
        ?int $maxStrokeCount = null,
        ?array $meanings = null,
        ?array $onyomi = null,
        ?array $kunyomi = null,
        ?string $radical = null,
        ?int $limit = null,
        ?int $offset = null,
        ?EntityId $articleId
    ): self {
        return new self(
            articleId: $articleId,
            character: $character ? new KanjiCharacter($character) : null,
            grade: $grade ? new KanjiGrade($grade) : null,
            jlpt: $jlpt ? new JlptLevel($jlpt) : null,
            minStrokeCount: $minStrokeCount,
            maxStrokeCount: $maxStrokeCount,
            meanings: $meanings,
            onyomi: $onyomi,
            kunyomi: $kunyomi,
            radical: $radical,
            pagination: new Pagination(page: $page, per_page: $perPage),
            limit: $limit,
            offset: $offset
        );
    }

    public static function byUuid(EntityId $uuid): self
    {
        return new self(uuid: $uuid);
    }

    public static function byCharacter(KanjiCharacter $character): self
    {
        return new self(character: $character);
    }
}
