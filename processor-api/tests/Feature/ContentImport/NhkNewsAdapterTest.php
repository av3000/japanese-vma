<?php

declare(strict_types=1);

namespace Tests\Feature\ContentImport;

use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;
use App\Infrastructure\ContentImport\Sources\Nhk\NhkNewsAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\ContentImport\FakesNhkNews;
use Tests\TestCase;

class NhkNewsAdapterTest extends TestCase
{
    use FakesNhkNews;

    public function test_it_lists_articles_newest_first_from_the_sitemap_and_page_json_ld(): void
    {
        $this->fakeNhkNews();

        $articles = $this->list();

        self::assertSame(
            ['nd-20261002de00003', 'nd-20261002de00002', 'nd-20261002de00001'],
            array_map(fn (ExternalArticle $a): string => $a->externalId, $articles),
        );

        $first = $articles[0];
        self::assertSame('新しい路面電車 来春から試験運行へ', $first->title);
        self::assertStringStartsWith('市内を走る新しい路面電車が', $first->lead);
        self::assertSame('https://news.web.nhk/newsweb/na/nd-20261002de00003', $first->canonicalUrl);
        self::assertSame(['社会', '暮らし'], $first->genres);
        self::assertSame(['交通' => '交通', 'まちづくり' => 'まちづくり'], $first->topics);
        self::assertSame('2026-10-02T22:30:00+09:00', $first->publishedAt?->format(DATE_ATOM));
        self::assertSame([], $articles[2]->topics);
    }

    public function test_it_stops_at_the_first_known_article_and_fetches_nothing_past_it(): void
    {
        $this->fakeNhkNews();

        $articles = $this->list(fn (string $id): bool => $id === 'nd-20261002de00002');

        self::assertCount(1, $articles);
        Http::assertNotSent(fn (Request $r): bool => str_contains($r->url(), 'nd-20261002de00002'));
        Http::assertNotSent(fn (Request $r): bool => str_contains($r->url(), 'nd-20261002de00001'));
    }

    public function test_the_listing_is_lazy(): void
    {
        $this->fakeNhkNews();

        foreach (app(NhkNewsAdapter::class)->listRecent(fn (): bool => false) as $article) {
            break;
        }

        Http::assertSentCount(3); // robots.txt, sitemap, one article page
    }

    public function test_every_request_carries_an_identifying_user_agent(): void
    {
        config(['app.url' => 'https://vma.example']);
        $this->fakeNhkNews();

        $this->list();

        Http::assertSent(fn (Request $r): bool => $r->header('User-Agent') === ['JapaneseVMABot/1.0 (+https://vma.example)']);
        Http::assertNotSent(fn (Request $r): bool => ! str_starts_with($r->header('User-Agent')[0] ?? '', 'JapaneseVMABot/1.0'));
    }

    public function test_robots_disallow_fails_the_run_before_the_sitemap_is_fetched(): void
    {
        $this->fakeNhkNews(['https://news.web.nhk/robots.txt' => Http::response("User-agent: *\nDisallow: /sitemap/\n")]);

        $this->expectException(ContentSourceUnavailableException::class);
        $this->expectExceptionMessage('disallows /sitemap/');

        try {
            $this->list();
        } finally {
            Http::assertNotSent(fn (Request $r): bool => $r->url() === self::NHK_SITEMAP);
        }
    }

    public function test_a_more_specific_allow_beats_a_broad_disallow(): void
    {
        $this->fakeNhkNews(['https://news.web.nhk/robots.txt' => Http::response(
            "User-agent: JapaneseVMABot\nDisallow: /\nAllow: /sitemap/\nAllow: /newsweb/na/\n\nUser-agent: *\nDisallow: /\n"
        )]);

        self::assertCount(3, $this->list());
    }

    public function test_a_missing_robots_file_allows_everything(): void
    {
        $this->fakeNhkNews(['https://news.web.nhk/robots.txt' => Http::response('', 404)]);

        self::assertCount(3, $this->list());
    }

    public function test_a_sitemap_that_changed_shape_fails_the_run(): void
    {
        $this->fakeNhkNews([self::NHK_SITEMAP => Http::response('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>')]);

        $this->expectException(ContentSourceUnavailableException::class);
        $this->expectExceptionMessage('expected a urlset');

        $this->list();
    }

    public function test_article_pages_without_a_news_article_block_fail_the_run(): void
    {
        $plain = Http::response('<html><head><title>x</title></head><body></body></html>');
        $this->fakeNhkNews(['https://news.web.nhk/newsweb/na/*' => $plain]);

        $this->expectException(ContentSourceUnavailableException::class);
        $this->expectExceptionMessage('NewsArticle');

        $this->list();
    }

    public function test_a_page_removed_after_the_sitemap_was_built_is_skipped(): void
    {
        $this->fakeNhkNews(['https://news.web.nhk/newsweb/na/nd-20261002de00003' => Http::response('', 404)]);

        $ids = array_map(fn (ExternalArticle $a): string => $a->externalId, $this->list());

        self::assertSame(['nd-20261002de00002', 'nd-20261002de00001'], $ids);
    }

    public function test_server_errors_are_retried_then_fail_the_run(): void
    {
        $this->fakeNhkNews([self::NHK_SITEMAP => Http::response('', 503)]);

        $this->expectException(ContentSourceUnavailableException::class);

        try {
            $this->list();
        } finally {
            Http::assertSentCount(4); // robots.txt + three sitemap attempts
        }
    }

    /**
     * @param (callable(string): bool)|null $isKnown
     *
     * @return list<ExternalArticle>
     */
    private function list(?callable $isKnown = null): array
    {
        $adapter = app(NhkNewsAdapter::class);

        return iterator_to_array($adapter->listRecent($isKnown ?? fn (): bool => false), false);
    }
}
