<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Commands\BenchmarkArticleProcessing;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BenchmarkArticleProcessingCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Approximate fixture sizes in characters, as promised by the benchmark: a short note,
     * a typical article and a long read. The bounds are loose on purpose; the point is that
     * the three samples stay an order of magnitude apart, not that they hit an exact count.
     *
     * @var array<string, array{int, int}>
     */
    private const EXPECTED_SIZES = [
        'short' => [250, 400],
        'typical' => [1200, 1700],
        'long' => [3400, 4200],
    ];

    public function test_benchmark_reports_query_count_and_timings_for_a_sample(): void
    {
        $exitCode = Artisan::call('article-processing:benchmark', ['--sample' => 'short', '--iterations' => 1]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Sample [short]', $output);
        $this->assertStringContainsString('Dictionary rows in japanese_word_bank_long', $output);
        $this->assertStringContainsString('Word queries', $output);
        $this->assertStringContainsString('p95/query ms', $output);
        $this->assertStringContainsString('Median over 1 run(s)', $output);
    }

    public function test_benchmark_rejects_an_unknown_sample(): void
    {
        $exitCode = Artisan::call('article-processing:benchmark', ['--sample' => 'enormous']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unknown sample [enormous]', Artisan::output());
    }

    public function test_benchmark_is_never_scheduled(): void
    {
        $scheduled = collect(app(Schedule::class)->events())
            ->first(fn (ScheduledEvent $event): bool => str_contains($event->command ?? '', 'article-processing:benchmark'));

        $this->assertNull($scheduled, 'The benchmark replays extraction over whole articles and must not run on a schedule.');
    }

    public function test_every_sample_fixture_is_japanese_prose_of_the_documented_size(): void
    {
        $this->assertSame(array_keys(self::EXPECTED_SIZES), BenchmarkArticleProcessing::SAMPLES);

        foreach (self::EXPECTED_SIZES as $sample => [$minimum, $maximum]) {
            $path = base_path("tests/Fixtures/articles/{$sample}.txt");

            $this->assertFileExists($path);

            $text = trim((string) file_get_contents($path));
            $length = mb_strlen($text, 'UTF-8');

            $this->assertGreaterThanOrEqual($minimum, $length, "Fixture [{$sample}] is shorter than advertised.");
            $this->assertLessThanOrEqual($maximum, $length, "Fixture [{$sample}] is longer than advertised.");

            $japanese = preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}]/u', $text);

            $this->assertGreaterThan(
                0.8 * $length,
                $japanese,
                "Fixture [{$sample}] must be Japanese prose; punctuation-heavy or latin text would not exercise extraction.",
            );
        }
    }
}
