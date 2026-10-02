<?php

declare(strict_types=1);

namespace Tests\Support\ContentImport;

use App\Application\ContentImport\Interfaces\ContentSourceAdapterInterface;
use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;

/**
 * Lists a fixed set of articles newest first and honours `$isKnown` like a real adapter.
 */
class FakeContentSourceAdapter implements ContentSourceAdapterInterface
{
    /** @var list<ExternalArticle> */
    public array $articles = [];

    public ?string $failWith = null;

    public int $yielded = 0;

    public function __construct(private readonly string $key = 'fake-source')
    {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function listRecent(callable $isKnown): iterable
    {
        foreach ($this->articles as $article) {
            if ($isKnown($article->externalId)) {
                return;
            }

            $this->yielded++;

            yield $article;
        }

        if ($this->failWith !== null) {
            throw new ContentSourceUnavailableException($this->failWith);
        }
    }

    /**
     * @param list<string> $genres
     */
    public static function article(string $id, array $genres = ['社会'], ?string $lead = null): ExternalArticle
    {
        return new ExternalArticle(
            externalId: $id,
            title: "記事 {$id}",
            lead: $lead ?? str_repeat('日本語のニュースの本文です。', 6),
            canonicalUrl: "https://news.example.jp/{$id}",
            publishedAt: new \DateTimeImmutable('2026-10-01T09:00:00+09:00'),
            genres: $genres,
            topics: ['T1' => 'テスト'],
        );
    }
}
