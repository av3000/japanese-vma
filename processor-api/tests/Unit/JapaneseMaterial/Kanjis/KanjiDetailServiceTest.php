<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Kanjis;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiDetailService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class KanjiDetailServiceTest extends TestCase
{
    private KanjiServiceInterface&MockObject $kanjiService;

    private WordServiceInterface&MockObject $wordService;

    private SentenceServiceInterface&MockObject $sentenceService;

    private ArticleServiceInterface&MockObject $articleService;

    private KanjiDetailService $service;

    protected function setUp(): void
    {
        $this->kanjiService = $this->createMock(KanjiServiceInterface::class);
        $this->wordService = $this->createMock(WordServiceInterface::class);
        $this->sentenceService = $this->createMock(SentenceServiceInterface::class);
        $this->articleService = $this->createMock(ArticleServiceInterface::class);

        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogueItemRepository = $this->createMock(CatalogueItemRepositoryInterface::class);

        $this->service = new KanjiDetailService(
            kanjiService: $this->kanjiService,
            wordService: $this->wordService,
            sentenceService: $this->sentenceService,
            articleService: $this->articleService,
            viewerCatalogueStateService: new ViewerCatalogueStateService(
                $catalogueRepository,
                $catalogueItemRepository,
            ),
        );

        $this->kanjiService->method('findByIdentifier')
            ->willReturn(Result::success($this->kanji()));
    }

    public function test_lean_detail_does_not_query_related_modules(): void
    {
        $this->wordService->expects($this->never())->method('find');
        $this->sentenceService->expects($this->never())->method('find');
        $this->articleService->expects($this->never())->method('getArticlesList');

        $result = $this->service->findByIdentifier(
            '水',
            new KanjiDetailIncludes,
            null,
        );

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getData()->words);
        $this->assertNull($result->getData()->sentences);
        $this->assertNull($result->getData()->articles);
    }

    public function test_complete_detail_queries_related_modules_with_kanji_id(): void
    {
        $this->wordService->expects($this->once())
            ->method('find')
            ->with($this->callback(
                fn (WordQueryCriteria $criteria): bool => $criteria->kanjiId === 88,
            ))
            ->willReturn(Result::success($this->emptyWordListResult()));

        $this->sentenceService->expects($this->once())
            ->method('find')
            ->with($this->callback(
                fn (SentenceQueryCriteria $criteria): bool => $criteria->kanjiId === 88,
            ))
            ->willReturn(Result::success($this->emptySentenceListResult()));

        $this->articleService->expects($this->once())
            ->method('getArticlesList')
            ->with(
                $this->callback(fn (ArticleListDTO $dto): bool => $dto->kanji_id === 88),
                null,
            )
            ->willReturn($this->emptyArticleListResult());

        $result = $this->service->findByIdentifier(
            'kanji-uuid',
            new KanjiDetailIncludes(words: true, sentences: true, articles: true),
            null,
        );

        $this->assertTrue($result->isSuccess());
    }

    private function kanji(): Kanji
    {
        return new Kanji(
            id: 88,
            uuid: EntityId::generate(),
            character: new KanjiCharacter('水'),
            onyomi: ['スイ'],
            kunyomi: ['みず'],
            meanings: ['water'],
            nanori: [],
            grade: null,
            strokeCount: 4,
            jlpt: null,
            frequency: 2,
            radicals: ['水'],
            radicalParts: ['水'],
        );
    }

    private function emptyWordListResult(): WordListResultDTO
    {
        return new WordListResultDTO([], $this->emptyPagination());
    }

    private function emptySentenceListResult(): SentenceListResultDTO
    {
        return new SentenceListResultDTO([], $this->emptyPagination());
    }

    private function emptyArticleListResult(): ArticleListResultDTO
    {
        return new ArticleListResultDTO([], $this->emptyPagination(), true, true);
    }

    /**
     * @return array{page: int, per_page: int, total: int, last_page: int, has_more: bool}
     */
    private function emptyPagination(): array
    {
        return ['page' => 1, 'per_page' => 5, 'total' => 0, 'last_page' => 1, 'has_more' => false];
    }
}
