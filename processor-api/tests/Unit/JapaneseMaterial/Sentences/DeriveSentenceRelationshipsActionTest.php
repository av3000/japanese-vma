<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Sentences;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionServiceInterface;
use App\Application\JapaneseMaterial\Sentences\Actions\DeriveSentenceRelationshipsAction;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeriveSentenceRelationshipsActionTest extends TestCase
{
    private KanjiExtractionServiceInterface&MockObject $kanjiExtraction;

    private KanjiRepositoryInterface&MockObject $kanjiRepository;

    private WordExtractionServiceInterface&MockObject $wordExtraction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kanjiExtraction = $this->createMock(KanjiExtractionServiceInterface::class);
        $this->kanjiRepository = $this->createMock(KanjiRepositoryInterface::class);
        $this->wordExtraction = $this->createMock(WordExtractionServiceInterface::class);
    }

    public function test_it_derives_unique_kanji_and_word_ids(): void
    {
        $content = '学校へ行きます。';
        $this->kanjiExtraction->expects(self::once())
            ->method('extractUniqueKanjis')
            ->with($content)
            ->willReturn(['学', '校']);
        $this->kanjiRepository->expects(self::once())
            ->method('findIdsByCharacters')
            ->with(['学', '校'])
            ->willReturn([10, 11, 10]);
        $this->wordExtraction->expects(self::once())
            ->method('extractWordIds')
            ->with($content)
            ->willReturn([20, 20, 21]);

        $result = $this->action()->execute($content);

        self::assertSame([10, 11], $result->kanjiIds);
        self::assertSame([20, 21], $result->wordIds);
    }

    public function test_it_returns_empty_relationship_sets(): void
    {
        $this->kanjiExtraction->method('extractUniqueKanjis')->willReturn([]);
        $this->kanjiRepository->method('findIdsByCharacters')->with([])->willReturn([]);
        $this->wordExtraction->method('extractWordIds')->willReturn([]);

        $result = $this->action()->execute('かなだけです。');

        self::assertSame([], $result->kanjiIds);
        self::assertSame([], $result->wordIds);
    }

    private function action(): DeriveSentenceRelationshipsAction
    {
        return new DeriveSentenceRelationshipsAction(
            $this->kanjiExtraction,
            $this->kanjiRepository,
            $this->wordExtraction,
        );
    }
}
