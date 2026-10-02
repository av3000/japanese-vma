<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\ContentImport\Enums\ImportRunStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ContentImportRun;
use App\Infrastructure\Persistence\Models\ContentSource;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\ContentImporterUserSeeder;
use Database\Seeders\ContentSourceSeeder;
use Database\Seeders\NhkTagMappingSeeder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\ContentImport\FakesNhkNews;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ImportContentCommandTest extends TestCase
{
    use FakesNhkNews, RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->seed([ContentSourceSeeder::class, NhkTagMappingSeeder::class, ContentImporterUserSeeder::class]);
        Bus::fake();
    }

    public function test_a_run_imports_nhk_articles_end_to_end_and_a_second_run_imports_nothing(): void
    {
        $this->fakeNhkNews();

        $this->artisan('content:import')
            ->expectsOutputToContain('nhk-news: listed 3, created 3, skipped 0, failed 0.')
            ->assertSuccessful();

        $systemUser = User::query()->where('email', config('content_import.system_user.email'))->sole();
        $article = Article::query()->where('external_id', 'nd-20261002de00003')->sole();

        self::assertSame('新しい路面電車 来春から試験運行へ', $article->title_jp);
        self::assertSame('https://news.web.nhk/newsweb/na/nd-20261002de00003', $article->source_link);
        self::assertSame($systemUser->id, $article->user_id);
        self::assertSame(ContentSource::query()->sole()->id, $article->content_source_id);
        $this->assertDatabaseHas('uniquehashtags', ['content' => '#交通']);
        $this->assertDatabaseHas('uniquehashtags', ['content' => '#社会']);

        $this->artisan('content:import')
            ->expectsOutputToContain('nhk-news: listed 0, created 0')
            ->assertSuccessful();

        self::assertSame(3, Article::query()->count());
        self::assertSame(2, ContentImportRun::query()->where('status', ImportRunStatus::Succeeded->value)->count());
    }

    public function test_a_dry_run_shows_what_would_be_created_and_writes_nothing(): void
    {
        $this->fakeNhkNews();

        $this->artisan('content:import', ['--source' => 'nhk-news', '--dry-run' => true])
            ->expectsOutputToContain('would_create')
            ->expectsOutputToContain('nhk-news: listed 3, would create 3')
            ->assertSuccessful();

        self::assertSame(0, Article::query()->count());
        self::assertSame(0, ContentImportRun::query()->count());
    }

    public function test_a_failed_run_exits_non_zero_and_is_recorded(): void
    {
        $this->fakeNhkNews([self::NHK_SITEMAP => Http::response('not xml at all')]);

        $this->artisan('content:import', ['--source' => 'nhk-news'])
            ->expectsOutputToContain('Run failed: NHK sitemap is not valid XML')
            ->assertFailed();

        self::assertSame(ImportRunStatus::Failed, ContentImportRun::query()->sole()->status);
    }

    public function test_an_unknown_source_exits_non_zero(): void
    {
        $this->fakeNhkNews();

        $this->artisan('content:import', ['--source' => 'nope'])->assertFailed();
    }

    public function test_it_warns_when_several_runs_in_a_row_created_nothing(): void
    {
        $this->fakeNhkNews();

        $this->artisan('content:import')->assertSuccessful();
        $this->artisan('content:import')->doesntExpectOutputToContain('several runs in a row')->assertSuccessful();
        $this->artisan('content:import')->doesntExpectOutputToContain('several runs in a row')->assertSuccessful();

        $this->artisan('content:import')
            ->expectsOutputToContain('nhk-news: several runs in a row created nothing')
            ->assertSuccessful();
    }

    public function test_it_is_scheduled_daily_in_the_japanese_morning_without_overlap(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'content:import'));

        self::assertCount(1, $events);
        $event = $events->first();
        self::assertSame('30 7 * * *', $event->expression);
        self::assertSame('Asia/Tokyo', (string) $event->timezone);
        self::assertTrue($event->withoutOverlapping);
    }
}
