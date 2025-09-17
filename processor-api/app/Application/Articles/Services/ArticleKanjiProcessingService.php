<?php
namespace App\Application\Articles\Services;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\Articles\Actions\Processing\ExtractKanjisAction;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\ValueObjects\{JlptLevels};

class ArticleKanjiProcessingService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private KanjiRepositoryInterface $kanjiRepository,
        private ExtractKanjisAction $extractKanjis
    ) {}

    public function processArticleKanjis(EntityId $articleUid): DomainArticle
    {
        $article = $this->articleRepository->findByUid($articleUid);

        if (!$article) {
            throw new ArticleNotFoundException("Article not found: {$articleUid->value()}");
        }

        $kanjiIds = $this->extractKanjis->execute($article->getContentJp()->value());

        $jlptLevels = $this->calculateJlptLevels($kanjiIds);

        $updatedArticle = new DomainArticle(
            $article->getUid(),
            $article->getAuthorId(),
            $article->getTitleJp(),
            $article->getTitleEn(),
            $article->getContentJp(),
            $article->getContentEn(),
            $article->getSourceUrl(),
            $article->getPublicity(),
            ArticleStatus::PROCESSED,
            $jlptLevels,
            $article->getTags(),
            $article->getCreatedAt(),
            new \DateTimeImmutable()
        );

        return $this->articleRepository->saveWithKanjis($updatedArticle, $kanjiIds);
    }

    private function calculateJlptLevels(array $kanjiIds): JlptLevels
    {
        $kanjis = $this->kanjiRepository->findByIds($kanjiIds);

        $levels = ['n1' => 0, 'n2' => 0, 'n3' => 0, 'n4' => 0, 'n5' => 0, 'uncommon' => 0];

        foreach ($kanjis as $kanji) {
            $jlptLevel = $kanji->getJlptLevel();
            if (in_array($jlptLevel, ['1', '2', '3', '4', '5'])) {
                $levels['n' . $jlptLevel]++;
            } else {
                $levels['uncommon']++;
            }
        }

        return new JlptLevels($levels['n1'], $levels['n2'], $levels['n3'], $levels['n4'], $levels['n5'], $levels['uncommon']);
    }
}
