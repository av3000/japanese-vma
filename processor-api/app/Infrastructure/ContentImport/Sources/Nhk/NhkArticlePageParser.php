<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport\Sources\Nhk;

use App\Domain\ContentImport\DTOs\ExternalArticle;
use DateTimeImmutable;
use Throwable;

/**
 * Reads the schema.org NewsArticle JSON-LD block NHK renders server-side on every article page.
 * Only the excerpt fields are taken: identifier, headline, description (NHK's own lead, which
 * it already truncates), genres, keywords and the publication date.
 */
class NhkArticlePageParser
{
    public function parse(string $html, string $pageUrl): ?ExternalArticle
    {
        foreach ($this->jsonLdBlocks($html) as $block) {
            if (($block['@type'] ?? null) !== 'NewsArticle') {
                continue;
            }

            $id = $this->text($block['identifier'] ?? null);
            $headline = $this->text($block['headline'] ?? null);
            $description = $this->text($block['description'] ?? null);

            if ($id === '' || $headline === '' || $description === '') {
                return null;
            }

            $canonical = $this->text($block['mainEntityOfPage']['@id'] ?? null);
            // The page JSON-LD has no topic ids, so a topic is keyed by its own name.
            $keywords = $this->texts($block['keywords'] ?? []);

            return new ExternalArticle(
                externalId: $id,
                title: $headline,
                lead: $description,
                canonicalUrl: $this->isNhkUrl($canonical) ? $canonical : $pageUrl,
                publishedAt: $this->date($this->text($block['datePublished'] ?? null)),
                genres: $this->texts($block['genre'] ?? []),
                topics: array_combine($keywords, $keywords),
            );
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonLdBlocks(string $html): array
    {
        preg_match_all('#<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>#si', $html, $matches);

        $blocks = [];

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);

            if (! is_array($decoded)) {
                continue;
            }

            // A block is either one object or a list of them (or an @graph).
            $candidates = array_is_list($decoded) ? $decoded : ($decoded['@graph'] ?? [$decoded]);

            foreach ($candidates as $candidate) {
                if (is_array($candidate)) {
                    $blocks[] = $candidate;
                }
            }
        }

        return $blocks;
    }

    /**
     * NHK writes language-tagged values (`{"@value": "...", "@language": "ja"}`); plain strings
     * are accepted too.
     */
    private function text(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['@value'] ?? null;
        }

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return list<string>
     */
    private function texts(mixed $values): array
    {
        $values = is_array($values) && array_is_list($values) ? $values : [$values];
        $texts = array_values(array_filter(array_map($this->text(...), $values), fn (string $t): bool => $t !== ''));

        return array_values(array_unique($texts));
    }

    private function date(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function isNhkUrl(string $url): bool
    {
        return $url !== '' && parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === NhkNewsAdapter::HOST;
    }
}
