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

    public function test_resolves_the_whole_text_in_a_single_batched_lookup(): void
    {
        $repository = new FakeWordRepository([
            '学' => 1,
            '学校' => 2,
            '勉強' => 3,
        ]);
        $service = new WordExtractionService($repository);

        $this->assertSame([2, 3], $service->extractWordIds('学校で勉強'));

        $this->assertCount(1, $repository->lookups, 'The walk must not go back to the dictionary per candidate.');
        $this->assertSame(
            $repository->lookups[0],
            array_values(array_unique($repository->lookups[0])),
            'Candidates are deduplicated before they reach the dictionary.',
        );
    }

    public function test_candidates_never_grow_past_the_longest_dictionary_entry(): void
    {
        $repository = new FakeWordRepository([
            '学' => 1,
            '学校' => 2,
        ]);
        $service = new WordExtractionService($repository);

        $service->extractWordIds('学校で勉強します');

        foreach ($repository->lookups[0] as $candidate) {
            $this->assertLessThanOrEqual(
                2,
                mb_strlen($candidate, 'UTF-8'),
                "Candidate [{$candidate}] is longer than any word in this dictionary.",
            );
        }
    }

    public function test_matches_entries_longer_than_a_short_fixed_window(): void
    {
        // The real dictionary holds entries of up to 33 characters, so the candidate window is
        // measured from the data rather than capped at a convenient small number.
        $longEntry = '国際連合教育科学文化機関憲章';
        $service = new WordExtractionService(new FakeWordRepository([
            '国際' => 1,
            $longEntry => 2,
        ]));

        $this->assertSame([2], $service->extractWordIds($longEntry));
    }

    public function test_an_empty_dictionary_costs_no_lookup(): void
    {
        $repository = new FakeWordRepository([]);
        $service = new WordExtractionService($repository);

        $this->assertSame([], $service->extractWordIds('学校で勉強します'));
        $this->assertSame([], $repository->lookups);
    }
}

final class FakeWordRepository implements WordRepositoryInterface
{
    /** @var list<list<string>> */
    public array $lookups = [];

    /**
     * @param array<string, int> $idsByWord
     */
    public function __construct(private readonly array $idsByWord)
    {
    }

    public function maxWordLength(): int
    {
        $lengths = array_map(
            static fn (string $word): int => mb_strlen($word, 'UTF-8'),
            array_keys($this->idsByWord),
        );

        return $lengths === [] ? 0 : max($lengths);
    }

    public function findIdsByWords(array $words): array
    {
        $this->lookups[] = array_values($words);

        return array_intersect_key($this->idsByWord, array_flip($words));
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
