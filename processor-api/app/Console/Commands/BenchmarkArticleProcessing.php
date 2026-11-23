<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Measures what article processing actually costs instead of estimating it. The word
 * extraction loop is the suspected hot spot: it asks the dictionary about every candidate
 * substring, so its cost is a query count, not a line count.
 *
 * Deliberately not scheduled and refused outside local/testing: it replays extraction over
 * full article texts and would hammer a production dictionary for no user-facing gain.
 */
class BenchmarkArticleProcessing extends Command
{
    /** @var list<string> */
    public const SAMPLES = ['short', 'typical', 'long'];

    private const FIXTURE_DIRECTORY = 'tests/Fixtures/articles';

    private const DICTIONARY_TABLE = 'japanese_word_bank_long';

    protected $signature = 'article-processing:benchmark
        {--sample=typical : Fixture to measure: short, typical or long}
        {--iterations=3 : Timed repetitions of the sample}';

    protected $description = 'Measure query count and wall time of word and kanji extraction against the bundled article fixtures';

    /** @var list<float> */
    private array $queryTimes = [];

    private bool $recording = false;

    public function handle(
        WordExtractionServiceInterface $wordExtraction,
        KanjiExtractionServiceInterface $kanjiExtraction,
    ): int {
        if ($this->getLaravel()->environment('production')) {
            $this->error('The extraction benchmark never runs against production data.');

            return self::FAILURE;
        }

        $sample = (string) $this->option('sample');

        if (! in_array($sample, self::SAMPLES, true)) {
            $this->error("Unknown sample [{$sample}]. Choose one of: ".implode(', ', self::SAMPLES).'.');

            return self::FAILURE;
        }

        $path = base_path(self::FIXTURE_DIRECTORY."/{$sample}.txt");

        if (! is_file($path)) {
            $this->error("Fixture [{$path}] is missing.");

            return self::FAILURE;
        }

        $text = trim((string) file_get_contents($path));
        $iterations = max(1, (int) $this->option('iterations'));

        $this->reportContext($sample, $text, $iterations);

        DB::listen(function (QueryExecuted $query): void {
            if ($this->recording) {
                $this->queryTimes[] = $query->time;
            }
        });

        $rows = [];

        for ($iteration = 1; $iteration <= $iterations; $iteration++) {
            $rows[] = $this->measure($iteration, $text, $wordExtraction, $kanjiExtraction);
        }

        $this->table(
            ['Run', 'Word queries', 'Word ms', 'p95/query ms', 'Words', 'Kanji ms', 'Kanji'],
            array_map(
                static fn (array $row): array => [
                    $row['run'],
                    number_format($row['queries']),
                    number_format($row['word_ms'], 1),
                    number_format($row['p95_ms'], 3),
                    number_format($row['words']),
                    number_format($row['kanji_ms'], 1),
                    number_format($row['kanjis']),
                ],
                $rows,
            ),
        );

        $this->line(sprintf(
            'Median over %d run(s): %s word queries, %s ms word extraction, %s ms kanji extraction.',
            $iterations,
            number_format($this->median(array_column($rows, 'queries'))),
            number_format($this->median(array_column($rows, 'word_ms')), 1),
            number_format($this->median(array_column($rows, 'kanji_ms')), 1),
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{run: int, queries: int, word_ms: float, p95_ms: float, words: int, kanji_ms: float, kanjis: int}
     */
    private function measure(
        int $iteration,
        string $text,
        WordExtractionServiceInterface $wordExtraction,
        KanjiExtractionServiceInterface $kanjiExtraction,
    ): array {
        $this->queryTimes = [];
        $this->recording = true;

        $startedAt = microtime(true);
        $wordIds = $wordExtraction->extractWordIds($text);
        $wordMilliseconds = (microtime(true) - $startedAt) * 1000;

        $this->recording = false;
        $queryTimes = $this->queryTimes;

        $startedAt = microtime(true);
        $kanjis = $kanjiExtraction->extractUniqueKanjis($text);
        $kanjiMilliseconds = (microtime(true) - $startedAt) * 1000;

        return [
            'run' => $iteration,
            'queries' => count($queryTimes),
            'word_ms' => $wordMilliseconds,
            'p95_ms' => $this->percentile($queryTimes, 0.95),
            'words' => count($wordIds),
            'kanji_ms' => $kanjiMilliseconds,
            'kanjis' => count($kanjis),
        ];
    }

    private function reportContext(string $sample, string $text, int $iterations): void
    {
        $this->info(sprintf(
            'Sample [%s]: %s characters, %d iteration(s).',
            $sample,
            number_format(mb_strlen($text, 'UTF-8')),
            $iterations,
        ));

        $this->line(sprintf(
            'Database driver: %s. Dictionary rows in %s: %s.',
            DB::connection()->getDriverName(),
            self::DICTIONARY_TABLE,
            number_format(DB::table(self::DICTIONARY_TABLE)->count()),
        ));

        $this->line('Indexes on '.self::DICTIONARY_TABLE.': '.$this->dictionaryIndexes());
    }

    private function dictionaryIndexes(): string
    {
        $names = array_filter(array_map(
            static fn (array $index): string => (string) ($index['name'] ?? ''),
            Schema::getIndexes(self::DICTIONARY_TABLE),
        ));

        return $names === [] ? 'none' : implode(', ', $names);
    }

    /**
     * @param list<float> $values
     */
    private function percentile(array $values, float $percentile): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);

        $index = (int) ceil($percentile * count($values)) - 1;

        return $values[max(0, min($index, count($values) - 1))];
    }

    /**
     * @param list<int>|list<float> $values
     */
    private function median(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);

        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1
            ? (float) $values[$middle]
            : ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
