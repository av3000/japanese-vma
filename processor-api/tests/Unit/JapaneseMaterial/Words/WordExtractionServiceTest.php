<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Words;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionService;
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
}

final class FakeWordRepository implements WordRepositoryInterface
{
    /**
     * @param array<string, int> $idsByWord
     */
    public function __construct(private readonly array $idsByWord)
    {
    }

    public function hasWordStartingWith(string $prefix): bool
    {
        foreach (array_keys($this->idsByWord) as $word) {
            if (str_starts_with($word, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function findIdByWord(string $word): ?int
    {
        return $this->idsByWord[$word] ?? null;
    }
}
