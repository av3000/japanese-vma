<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Activity\ActivitySourceContext;
use App\Domain\Activity\Enums\ActivitySource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks every activity row written during this request with the surface it came through, for
 * example `activity.source:api` on the API route group.
 */
final class SetActivitySource
{
    public function __construct(private readonly ActivitySourceContext $context)
    {
    }

    public function handle(Request $request, Closure $next, string $source): Response
    {
        $this->context->set(ActivitySource::from($source));

        return $next($request);
    }
}
