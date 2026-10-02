<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Controllers;

use App\Application\JapaneseMaterial\Radicals\Services\RadicalServiceInterface;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Radicals\Requests\IndexRadicalRequest;
use App\Http\v1\JapaneseMaterial\Radicals\Resources\RadicalListResource;
use App\Http\v1\JapaneseMaterial\Radicals\Resources\RadicalResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class RadicalController extends Controller
{
    public function __construct(
        private readonly RadicalServiceInterface $radicalService,
    ) {
    }

    #[Response(type: 'RadicalListResource')]
    public function index(IndexRadicalRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();

        $criteria = RadicalQueryCriteria::forListing(
            page: $validated['page'] ?? Pagination::MIN_PAGE,
            perPage: $validated['per_page'] ?? RadicalQueryCriteria::DEFAULT_PER_PAGE,
            keyword: $validated['keyword'] ?? null,
            radical: $validated['radical'] ?? null,
            meaning: $validated['meaning'] ?? null,
            hiragana: $validated['hiragana'] ?? null,
            strokes: $validated['strokes'] ?? null,
        );

        $result = $this->radicalService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new RadicalListResource($result->getData());
    }

    #[Response(type: 'RadicalResource')]
    public function show(string $identifier): JsonResponse|JsonResource
    {
        $result = $this->radicalService->findByIdentifier($identifier, withKanjis: true);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new RadicalResource($result->getData(), includeKanjis: true);
    }
}
