<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Guards the word extraction query budget.
 *
 * The per-character walk this replaced issued roughly three queries per character, so a
 * 4,000 character article meant about 11,000 round trips and six seconds against a fully
 * indexed dictionary. What matters here is not the absolute number but that the count is
 * a small function of the candidate set rather than of the text length, which is invisible
 * on a one-sentence fixture and fatal on a real article.
 */
class WordExtractionQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_extraction_stays_far_below_one_query_per_character(): void
    {
        $this->seedDictionary(['学校' => 1, '勉強' => 2, '毎日' => 3, '先生' => 4, '教室' => 5]);

        $sentence = '毎日学校の教室で先生と勉強します。';
        $short = str_repeat($sentence, 12);
        $long = str_repeat($sentence, 60);

        $shortQueries = $this->countQueries(fn () => $this->extract($short));
        $longQueries = $this->countQueries(fn () => $this->extract($long));

        $this->assertLessThan(10, $shortQueries);
        $this->assertLessThan(10, $longQueries);
        $this->assertLessThan(
            mb_strlen($long, 'UTF-8') / 100,
            $longQueries,
            'Query count must not track text length.',
        );
    }

    public function test_extraction_returns_greedy_longest_matches_against_a_real_dictionary(): void
    {
        $this->seedDictionary(['学' => 10, '学校' => 11, '校' => 12, '勉強' => 13]);

        $this->assertSame([11, 13], $this->extract('学校で勉強します'));
    }

    public function test_repeated_surfaces_resolve_to_the_lowest_id(): void
    {
        // The dictionary holds about 1,400 surfaces more than once, one row per sense. The
        // batched lookup has to pick one, and it always picks the same one.
        $this->seedDictionary(['橋' => 90]);
        $this->seedDictionary(['橋' => 40]);
        $this->seedDictionary(['橋' => 70]);

        $this->assertSame([40], $this->extract('橋'));
    }

    public function test_max_word_length_is_read_from_the_dictionary(): void
    {
        $repository = app(WordRepositoryInterface::class);

        $this->assertSame(0, $repository->maxWordLength(), 'An empty dictionary bounds candidates to nothing.');

        $this->seedDictionary(['橋' => 1, '国際連合教育科学文化機関' => 2]);

        $this->assertSame(12, $repository->maxWordLength());
    }

    /**
     * @return array<int, int>
     */
    private function extract(string $text): array
    {
        return app(WordExtractionServiceInterface::class)->extractWordIds($text);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    }

    /**
     * @param array<string, int> $idsByWord
     */
    private function seedDictionary(array $idsByWord): void
    {
        DB::table('japanese_word_bank_long')->insert(array_map(
            static fn (string $word, int $id): array => [
                'id' => $id,
                'entry_sequence' => (string) $id,
                'word' => $word,
                'furigana' => $word,
                'jlpt' => 'n5',
                'word_type' => 'noun',
                'word_k_ele' => $word,
                'furigana_r_ele' => $word,
                'sense' => 'test entry',
                'uuid' => (string) Str::uuid(),
            ],
            array_keys($idsByWord),
            array_values($idsByWord),
        ));
    }
}
