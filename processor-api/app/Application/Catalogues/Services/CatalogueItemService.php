<?php

namespace App\Application\Catalogues\Services;

use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Services\TemplateTypeClassifier;
use App\Http\Models\Kanji;
use App\Http\Models\Radical;
use App\Http\Models\Sentence;
use App\Http\Models\Word;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Readers\ArticleListFilterBuilder;
use Illuminate\Database\Eloquent\Builder;

class CatalogueItemService
{
    public function __construct(
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly TemplateTypeClassifier $templateTypeClassifier,
        private readonly HashtagServiceInterface $hashtagService,
        private readonly LoadEntityStatsAction $loadStats,
        private readonly ArticlePolicy $articlePolicy,
        private readonly ArticleListFilterBuilder $articleFilterBuilder,
    ) {
    }

    /**
     * The catalogue's items as the viewer may see them. An article catalogue can hold
     * articles that are private to someone else (saved while public, or private all
     * along), so article items go through the same visibility scope as the article list.
     */
    public function getItems(Catalogue $catalogue, ?AuthenticatedUser $viewer): array
    {
        $listType = $catalogue->getType();
        $baseType = $this->templateTypeClassifier->getBaseType($listType) ?? $listType;

        $itemIds = $this->catalogueItemRepository->findItemIdsByCatalogueId($catalogue->getIdValue());
        if (empty($itemIds)) {
            return [];
        }

        $savesMap = $this->catalogueItemRepository->countSavesByItemIds($itemIds, $listType->value);

        return match ($baseType) {
            SavedListType::RADICALS => $this->mapRadicals($itemIds, $savesMap),
            SavedListType::KANJIS => $this->mapKanjis($itemIds, $savesMap),
            SavedListType::WORDS => $this->mapWords($itemIds, $savesMap),
            SavedListType::SENTENCES => $this->mapSentences($itemIds, $savesMap),
            SavedListType::ARTICLES => $this->mapArticles($itemIds, $savesMap, $viewer),
            default => [],
        };
    }

    /**
     * An article is a valid item only if the actor may open it, so no one can add,
     * and then read back the title of, another user's private article.
     */
    public function isValidItemForCatalogue(Catalogue $catalogue, int $itemId, AuthenticatedUser $actor): bool
    {
        $listType = $catalogue->getType();
        $baseType = $this->templateTypeClassifier->getBaseType($listType) ?? $listType;

        return match ($baseType) {
            SavedListType::RADICALS => Radical::query()->whereKey($itemId)->exists(),
            SavedListType::KANJIS => Kanji::query()->whereKey($itemId)->exists(),
            SavedListType::WORDS => Word::query()->whereKey($itemId)->exists(),
            SavedListType::SENTENCES => Sentence::query()->whereKey($itemId)->exists(),
            SavedListType::ARTICLES => $this->visibleArticles($actor)->whereKey($itemId)->exists(),
            default => false,
        };
    }

    public function addItem(Catalogue $catalogue, int $itemId): void
    {
        $this->catalogueItemRepository->addItem(
            $catalogue->getIdValue(),
            $catalogue->getType(),
            $itemId
        );
    }

    public function removeItem(Catalogue $catalogue, int $itemId): bool
    {
        return $this->catalogueItemRepository->removeItem(
            $catalogue->getIdValue(),
            $catalogue->getType(),
            $itemId
        );
    }

    private function mapRadicals(array $itemIds, array $savesMap): array
    {
        return Radical::whereIn('id', $itemIds)
            ->get()
            ->map(function ($radical) use ($savesMap) {
                return [
                    'id' => $radical->id,
                    'radical' => $radical->radical,
                    'meaning' => $radical->meaning,
                    'strokes' => $radical->strokes,
                    'hiragana' => $radical->hiragana,
                    'saves_count' => $savesMap[$radical->id] ?? 0,
                ];
            })
            ->toArray();
    }

    private function mapKanjis(array $itemIds, array $savesMap): array
    {
        return Kanji::whereIn('id', $itemIds)
            ->get()
            ->map(function ($kanji) use ($savesMap) {
                return [
                    'id' => $kanji->id,
                    'kanji' => $kanji->kanji,
                    'onyomi' => $kanji->onyomi,
                    'kunyomi' => $kanji->kunyomi,
                    'meaning' => $kanji->meaning,
                    'jlpt' => $kanji->jlpt,
                    'frequency' => $kanji->frequency,
                    'saves_count' => $savesMap[$kanji->id] ?? 0,
                ];
            })
            ->toArray();
    }

    private function mapWords(array $itemIds, array $savesMap): array
    {
        $words = Word::whereIn('id', $itemIds)->get();
        $words = extractWordsListAttributes($words);
        if ($words === false) {
            return [];
        }

        return collect($words)
            ->map(function ($word) use ($savesMap) {
                return [
                    'id' => $word->id,
                    'word' => $word->word,
                    'furigana' => $word->furigana,
                    'meaning' => $word->meaning,
                    'jlpt' => $word->jlpt ?? null,
                    'word_type' => $word->word_type ?? null,
                    'saves_count' => $savesMap[$word->id] ?? 0,
                ];
            })
            ->toArray();
    }

    private function mapSentences(array $itemIds, array $savesMap): array
    {
        return Sentence::whereIn('id', $itemIds)
            ->get()
            ->map(function ($sentence) use ($savesMap) {
                return [
                    'id' => $sentence->id,
                    'content' => $sentence->content,
                    'tatoeba_entry' => $sentence->tatoeba_entry,
                    'saves_count' => $savesMap[$sentence->id] ?? 0,
                ];
            })
            ->toArray();
    }

    private function visibleArticles(?AuthenticatedUser $viewer): Builder
    {
        $query = PersistenceArticle::query();
        $this->articleFilterBuilder->applyVisibilityScope($query, $this->articlePolicy->scopeFor($viewer));

        return $query;
    }

    private function mapArticles(array $itemIds, array $savesMap, ?AuthenticatedUser $viewer): array
    {
        $articles = $this->visibleArticles($viewer)->whereIn('id', $itemIds)->get();
        $articleIds = $articles->pluck('id')->toArray();

        $hashtagsMap = $this->hashtagService->getBatchHashtags($articleIds, ObjectTemplateType::ARTICLE);
        $statsData = $this->loadStats->batchLoadStatsById(ObjectTemplateType::ARTICLE->getLegacyId(), $articleIds);

        return $articles->map(function ($article) use ($hashtagsMap, $statsData, $savesMap) {
            $stats = $statsData[$article->id] ?? [
                'likes' => 0,
                'downloads' => 0,
                'views' => 0,
                'comments' => 0,
            ];

            return [
                'id' => $article->id,
                'uuid' => $article->uuid,
                'title_jp' => $article->title_jp,
                'hashtags' => $hashtagsMap[$article->id] ?? [],
                'engagement' => [
                    'likes_count' => $stats['likes'],
                    'downloads_count' => $stats['downloads'],
                    'views_count' => $stats['views'],
                    'comments_count' => $stats['comments'],
                ],
                'saves_count' => $savesMap[$article->id] ?? 0,
            ];
        })->toArray();
    }
}
