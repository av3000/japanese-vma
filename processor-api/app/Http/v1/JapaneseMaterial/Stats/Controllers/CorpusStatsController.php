<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Stats\Controllers;

use App\Application\JapaneseMaterial\Stats\Services\CorpusStatsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Stats\Resources\CorpusStatsResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class CorpusStatsController extends Controller
{
    public function __construct(
        private readonly CorpusStatsServiceInterface $corpusStatsService,
    ) {
    }

    /**
     * Corpus totals for the landing page.
     *
     * Public and cached: up to an hour server-side, five minutes in the browser.
     */
    #[Response(type: 'CorpusStatsResource')]
    public function show(): JsonResponse|JsonResource
    {
        $result = $this->corpusStatsService->get();

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new CorpusStatsResource($result->getData());
    }
}
