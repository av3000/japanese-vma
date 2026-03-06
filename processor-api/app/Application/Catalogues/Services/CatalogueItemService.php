<?php

namespace App\Application\Catalogues\Services;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\{ObjectTemplateType, SavedListType};
use App\Domain\Shared\Services\TemplateTypeClassifier;
use App\Http\Models\{Radical, Kanji, Word, Sentence};
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;

class CatalogueItemService
{
    public function __construct(
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly TemplateTypeClassifier $templateTypeClassifier,
        private readonly HashtagServiceInterface $hashtagService,
        private readonly LoadEntityStatsAction $loadStats,
    ) {}

    public function getItems(Catalogue $catalogue): array
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
            SavedListType::ARTICLES => $this->mapArticles($itemIds, $savesMap),
            default => [],
        };
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

    private function mapArticles(array $itemIds, array $savesMap): array
    {
        $articles = PersistenceArticle::whereIn('id', $itemIds)->get();
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
