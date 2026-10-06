<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport\Sources\Nhk;

use App\Application\ContentImport\Interfaces\ContentSourceAdapterInterface;
use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;
use App\Infrastructure\ContentImport\Http\PoliteHttpClient;
use App\Infrastructure\ContentImport\Http\RobotsTxtPolicy;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

/**
 * NHK News, read the way its robots.txt allows: the Google News sitemap on news.web.nhk lists
 * the newest articles, and each article page carries a NewsArticle JSON-LD block.
 *
 * NHK's JSON API on api.web.nhk is deliberately not used: its robots.txt disallows every user
 * agent. Neither the sitemap nor the JSON-LD is a documented contract, so a response that no
 * longer has the expected shape fails the run instead of importing garbage.
 */
class NhkNewsAdapter implements ContentSourceAdapterInterface
{
    public const KEY = 'nhk-news';

    public const HOST = 'news.web.nhk';

    /**
     * Consecutive article pages that could not be read (an error answer, or no usable NewsArticle
     * block) before the run gives up. One bad page is skipped and listed again next run; several
     * in a row mean the site is down or changed shape.
     */
    private const MAX_UNREADABLE_IN_A_ROW = 3;

    public function __construct(
        private readonly PoliteHttpClient $http,
        private readonly RobotsTxtPolicy $robots,
        private readonly NhkArticlePageParser $parser,
        private readonly string $sitemapUrl,
    ) {
    }

    public function key(): string
    {
        return self::KEY;
    }

    public function listRecent(callable $isKnown): iterable
    {
        $unreadableInARow = 0;
        $parsed = 0;

        $unreadable = function (string $problem) use (&$unreadableInARow): void {
            if (++$unreadableInARow >= self::MAX_UNREADABLE_IN_A_ROW) {
                throw new ContentSourceUnavailableException("NHK article pages keep failing: {$problem}");
            }

            Log::warning('NHK article page skipped', ['problem' => $problem]);
        };

        foreach ($this->sitemapEntries() as $entry) {
            if ($isKnown($entry['id'])) {
                continue;
            }

            $this->robots->assertAllowed($entry['url']);
            $response = $this->http->get($entry['url']);

            // A page removed between the sitemap and the fetch is skipped, not fatal.
            if ($response->status() === 404 || $response->status() === 410) {
                continue;
            }

            if (! $response->successful()) {
                $unreadable("{$entry['url']} answered {$response->status()}");

                continue;
            }

            $article = $this->parser->parse($response->body(), $entry['url']);

            if ($article === null || $article->externalId !== $entry['id']) {
                $unreadable("{$entry['url']} carries no readable NewsArticle block");

                continue;
            }

            $unreadableInARow = 0;
            $parsed++;

            yield $article;
        }

        if ($unreadableInARow > 0 && $parsed === 0) {
            throw new ContentSourceUnavailableException('No NHK article page in the sitemap could be read');
        }
    }

    /**
     * Newest first.
     *
     * @return list<array{id: string, url: string, publishedAt: int|null}>
     */
    private function sitemapEntries(): array
    {
        $this->robots->assertAllowed($this->sitemapUrl);
        $response = $this->http->get($this->sitemapUrl);

        if (! $response->successful()) {
            throw new ContentSourceUnavailableException('NHK sitemap answered '.$response->status());
        }

        try {
            $xml = new SimpleXMLElement($response->body(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } catch (Throwable $e) {
            throw new ContentSourceUnavailableException('NHK sitemap is not valid XML', 0, $e);
        }

        if ($xml->getName() !== 'urlset') {
            throw new ContentSourceUnavailableException('NHK sitemap is a '.$xml->getName().', expected a urlset');
        }

        $entries = [];

        foreach ($xml->url as $url) {
            $loc = trim((string) $url->loc);
            $news = $url->children('http://www.google.com/schemas/sitemap-news/0.9')->news;
            $publishedAt = trim((string) ($news->publication_date ?? '')) ?: trim((string) $url->lastmod);

            if (! $this->isArticleUrl($loc)) {
                continue;
            }

            $entries[] = [
                'id' => basename((string) parse_url($loc, PHP_URL_PATH)),
                'url' => $loc,
                'publishedAt' => $this->timestamp($publishedAt),
            ];
        }

        if ($entries === [] && count($xml->url) > 0) {
            throw new ContentSourceUnavailableException('NHK sitemap lists no article URLs in the expected form');
        }

        // Stable sort, newest first, compared as instants so differing offsets cannot misorder
        // them. An entry without a readable date goes last.
        usort($entries, fn (array $a, array $b): int => ($b['publishedAt'] ?? PHP_INT_MIN) <=> ($a['publishedAt'] ?? PHP_INT_MIN));

        return $entries;
    }

    private function timestamp(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    private function isArticleUrl(string $url): bool
    {
        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_HOST) === self::HOST
            && preg_match('#^/newsweb/na/[A-Za-z0-9-]{4,64}$#', (string) parse_url($url, PHP_URL_PATH)) === 1;
    }
}
