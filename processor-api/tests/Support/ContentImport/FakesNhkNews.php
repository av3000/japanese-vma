<?php

declare(strict_types=1);

namespace Tests\Support\ContentImport;

use Illuminate\Support\Facades\Http;

/**
 * Serves the synthetic NHK fixtures in tests/Fixtures/nhk through Http::fake().
 */
trait FakesNhkNews
{
    protected const NHK_SITEMAP = 'https://news.web.nhk/sitemap/sitemap-news-nationwide-article.xml';

    /**
     * @param array<string, mixed> $overrides URL (or pattern) => response; matched before the defaults
     */
    protected function fakeNhkNews(array $overrides = []): void
    {
        config(['content_import.http.request_interval_ms' => 0]);
        Http::preventStrayRequests();

        $fixture = fn (string $name): string => (string) file_get_contents(base_path("tests/Fixtures/nhk/{$name}"));

        // Union, not array_merge: an override must win, and must be matched before the defaults.
        Http::fake($overrides + [
            'https://news.web.nhk/robots.txt' => Http::response($fixture('robots.txt')),
            self::NHK_SITEMAP => Http::response($fixture('sitemap-news-nationwide-article.xml')),
            'https://news.web.nhk/newsweb/na/nd-20261002de00001' => Http::response($fixture('article-nd-20261002de00001.html')),
            'https://news.web.nhk/newsweb/na/nd-20261002de00002' => Http::response($fixture('article-nd-20261002de00002.html')),
            'https://news.web.nhk/newsweb/na/nd-20261002de00003' => Http::response($fixture('article-nd-20261002de00003.html')),
        ]);
    }
}
