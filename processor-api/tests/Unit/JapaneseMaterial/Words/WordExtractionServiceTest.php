<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Words;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionService;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use LogicException;
use Tests\TestCase;

class WordExtractionServiceTest extends TestCase
{
    public function test_extracts_longest_matching_words_from_japanese_text(): void
    {
        $service = new WordExtractionService(new FakeWordRepository([
            '学' => 1,
            '学校' => 2,
            '勉強' => 3,
        ]));

        $this->assertSame([2, 3], $service->extractWordIds('学校で勉強します'));
    }

    public function test_strips_spaces_and_line_breaks_before_matching(): void
    {
        $service = new WordExtractionService(new FakeWordRepository([
            '学校' => 2,
            '勉強' => 3,
        ]));

        $this->assertSame([2, 3], $service->extractWordIds("学校\n で\r 勉強します"));
    }

    public function test_deduplicates_repeated_words(): void
    {
        $service = new WordExtractionService(new FakeWordRepository([
            '学校' => 2,
        ]));

        $this->assertSame([2], $service->extractWordIds('学校学校'));
    }

    public function test_returns_empty_array_when_no_dictionary_words_match(): void
    {
        $service = new WordExtractionService(new FakeWordRepository([
            '学校' => 2,
        ]));

        $this->assertSame([], $service->extractWordIds('かなだけ'));
    }

    public function test_looks_up_each_accepted_candidate_once_and_never_again_after_the_loop(): void
    {
        $repository = new FakeWordRepository([
            '学' => 1,
            '学校' => 2,
            '勉強' => 3,
        ]);
        $service = new WordExtractionService($repository);

        $this->assertSame([2, 3], $service->extractWordIds('学校で勉強'));

        // Candidate steps that pass hasWordStartingWith: 学, 学校, 学校で(no) | で(no) | 勉, 勉強.
        // 学校で and で are rejected by the prefix check before any id lookup.
        $this->assertSame(['学', '学校', '勉', '勉強'], $repository->findIdByWordCalls);
        $this->assertSame(
            count($repository->hasWordStartingWithCalls) - 2,
            count($repository->findIdByWordCalls),
            'Exactly one findIdByWord per accepted prefix, none for the two rejected prefixes, none after the loop.',
        );
    }
}

final class FakeWordRepository implements WordRepositoryInterface
{
    /** @var list<string> */
    public array $hasWordStartingWithCalls = [];

    /** @var list<string> */
    public array $findIdByWordCalls = [];

    /**
     * @param array<string, int> $idsByWord
     */
    public function __construct(private readonly array $idsByWord)
    {
    }

    public function hasWordStartingWith(string $prefix): bool
    {
        $this->hasWordStartingWithCalls[] = $prefix;

        foreach (array_keys($this->idsByWord) as $word) {
            if (str_starts_with($word, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function findIdByWord(string $word): ?int
    {
        $this->findIdByWordCalls[] = $word;

        return $this->idsByWord[$word] ?? null;
    }

    public function find(WordQueryCriteria $criteria): WordListResultDTO
    {
        throw new LogicException('This fake only supports word extraction lookups.');
    }

    public function findByUuid(EntityId $uuid): ?Word
    {
        return null;
    }

    public function findBySurface(string $surface): ?Word
    {
        return null;
    }

    public function findRelatedKanjis(int $wordId, int $limit): array
    {
        return [];
    }
}
