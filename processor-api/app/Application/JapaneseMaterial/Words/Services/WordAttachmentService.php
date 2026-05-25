<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class WordAttachmentService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * @param array<int, int> $wordIds
     */
    public function attachWordsToArticle(EntityId $articleUuid, array $wordIds): Result
    {
        $articleId = $this->articleRepository->getIdByUuid($articleUuid);

        if (! $articleId) {
            return Result::failure(ArticleErrors::notFound($articleUuid->value()));
        }

        $uniqueWordIds = array_values(array_unique($wordIds));

        $this->articleRepository->syncWords($articleId, $uniqueWordIds);

        return Result::success($uniqueWordIds);
    }
}
