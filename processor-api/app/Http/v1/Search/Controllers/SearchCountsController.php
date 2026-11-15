<?php

declare(strict_types=1);

namespace App\Http\v1\Search\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Search\Services\SearchCountsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\v1\Search\Requests\SearchCountsRequest;
use App\Http\v1\Search\Resources\SearchCountsResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchCountsController extends Controller
{
    public function __construct(
        private readonly SearchCountsServiceInterface $searchCountsService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Per-scope hit counts for the Header search.
     *
     * Each count equals that list's `pagination.total` for the same keyword and viewer. A
     * null count means that list rejects the keyword: articles need at least two
     * characters, sentences and radicals take at most 100. `q` echoes the trimmed keyword
     * so the client can drop an answer to a query it no longer shows.
     *
     * Rate-limited on its own (60 a minute per user or IP), outside the shared API limit.
     */
    #[Response(type: 'SearchCountsResource')]
    public function index(SearchCountsRequest $request): JsonResponse|JsonResource
    {
        $result = $this->searchCountsService->count(
            $request->keyword(),
            $this->currentUserProvider->currentAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new SearchCountsResource($result->getData());
    }
}
