<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport\Http;

use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

/**
 * The only way the Content Import context talks to the outside world: an honest User-Agent that
 * names the bot and where it lives, a timeout, a fixed pause between requests, and a short retry
 * on connection errors and 5xx answers only.
 */
class PoliteHttpClient
{
    private const PRODUCT_TOKEN = 'JapaneseVMABot';

    private ?float $lastRequestAt = null;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly int $intervalMs = 1000,
        private readonly int $timeoutSeconds = 15,
    ) {
    }

    public function productToken(): string
    {
        return self::PRODUCT_TOKEN;
    }

    public function userAgent(): string
    {
        return self::PRODUCT_TOKEN.'/1.0 (+'.rtrim((string) config('app.url'), '/').')';
    }

    /**
     * The response, whatever its status; null when the request could not be made at all.
     */
    public function get(string $url): ?Response
    {
        $this->pause();

        try {
            return $this->http
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->timeout($this->timeoutSeconds)
                ->retry(
                    3, // attempts in total: the first try and two retries
                    500,
                    fn (\Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->get($url);
        } catch (ConnectionException $e) {
            throw new ContentSourceUnavailableException("Could not reach {$url}: {$e->getMessage()}", 0, $e);
        } finally {
            $this->lastRequestAt = microtime(true);
        }
    }

    private function pause(): void
    {
        if ($this->lastRequestAt === null || $this->intervalMs <= 0) {
            return;
        }

        $remainingUs = (int) ((($this->lastRequestAt + $this->intervalMs / 1000) - microtime(true)) * 1_000_000);

        if ($remainingUs > 0) {
            usleep($remainingUs);
        }
    }
}
