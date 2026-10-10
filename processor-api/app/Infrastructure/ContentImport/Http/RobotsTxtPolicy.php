<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport\Http;

use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;

/**
 * A small RFC 9309 robots.txt reader: one fetch per host per run, the group for our product
 * token if there is one (otherwise `*`), and the longest matching Allow/Disallow rule wins.
 *
 * A missing robots.txt (4xx) allows everything. One that cannot be fetched (5xx, network) is
 * treated as disallowing everything, as the RFC asks, and fails the run.
 */
class RobotsTxtPolicy
{
    /** @var array<string, list<array{allow: bool, path: string}>> rules per scheme://host */
    private array $rulesByOrigin = [];

    public function __construct(
        private readonly PoliteHttpClient $http,
    ) {
    }

    /**
     * @throws ContentSourceUnavailableException
     */
    public function assertAllowed(string $url): void
    {
        $origin = $this->origin($url);
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        $query = parse_url($url, PHP_URL_QUERY);
        $path .= is_string($query) ? '?'.$query : '';

        $this->rulesByOrigin[$origin] ??= $this->fetchRules($origin);

        if (! $this->isAllowed($this->rulesByOrigin[$origin], $path)) {
            throw new ContentSourceUnavailableException("robots.txt at {$origin} disallows {$path}");
        }
    }

    /**
     * @param list<array{allow: bool, path: string}> $rules
     */
    private function isAllowed(array $rules, string $path): bool
    {
        $best = null;

        foreach ($rules as $rule) {
            if ($rule['path'] === '' || ! $this->matches($rule['path'], $path)) {
                continue;
            }

            $length = strlen($rule['path']);

            // Longest match wins; on a tie, Allow wins (RFC 9309 section 2.2.2).
            if ($best === null || $length > $best['length'] || ($length === $best['length'] && $rule['allow'])) {
                $best = ['length' => $length, 'allow' => $rule['allow']];
            }
        }

        return $best === null || $best['allow'];
    }

    private function matches(string $pattern, string $path): bool
    {
        $anchored = str_ends_with($pattern, '$');
        $pattern = $anchored ? substr($pattern, 0, -1) : $pattern;
        $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).($anchored ? '$' : '').'#';

        return preg_match($regex, $path) === 1;
    }

    /**
     * @return list<array{allow: bool, path: string}>
     */
    private function fetchRules(string $origin): array
    {
        $response = $this->http->get($origin.'/robots.txt');

        if ($response->clientError()) {
            return [];
        }

        if (! $response->successful()) {
            throw new ContentSourceUnavailableException("robots.txt at {$origin} answered {$response->status()}");
        }

        return $this->parse($response->body(), $this->http->productToken());
    }

    /**
     * @return list<array{allow: bool, path: string}>
     */
    private function parse(string $body, string $productToken): array
    {
        $groups = [];
        $agents = [];
        $rules = [];
        $inRules = false;

        $flush = function () use (&$groups, &$agents, &$rules): void {
            foreach ($agents as $agent) {
                $groups[$agent] = array_merge($groups[$agent] ?? [], $rules);
            }
            $agents = [];
            $rules = [];
        };

        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            $line = trim((string) preg_replace('/#.*$/', '', $line));

            if (! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if ($inRules) {
                    $flush();
                    $inRules = false;
                }
                $agents[] = strtolower($value);
            } elseif ($field === 'allow' || $field === 'disallow') {
                $inRules = true;
                $rules[] = ['allow' => $field === 'allow', 'path' => $value];
            }
        }

        $flush();

        return $groups[strtolower($productToken)] ?? $groups['*'] ?? [];
    }

    private function origin(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return "{$scheme}://{$host}";
    }
}
