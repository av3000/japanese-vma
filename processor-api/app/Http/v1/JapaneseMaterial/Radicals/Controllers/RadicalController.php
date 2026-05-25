<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Controllers;

use App\Application\JapaneseMaterial\Radicals\Services\RadicalServiceInterface;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\JapaneseMaterial\Radicals\Requests\IndexRadicalRequest;
use App\Http\v1\JapaneseMaterial\Radicals\Resources\RadicalListResource;
use App\Http\v1\JapaneseMaterial\Radicals\Resources\RadicalResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class RadicalController extends Controller
{
    public function __construct(
        private readonly RadicalServiceInterface $radicalService,
    ) {}

    /**
     * @response array{
     *     items: array<int, array{
     *         id: int,
     *         uuid: string,
     *         radical: string|null,
     *         strokes: int|null,
     *         meaning: string|null,
     *         hiragana: string|null
     *     }>,
     *     pagination: PaginationResource
     * }
     */
    #[Response(type: 'array{items: array<int, array{id: int, uuid: string, radical: string|null, strokes: int|null, meaning: string|null, hiragana: string|null}>, pagination: PaginationResource}')]
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

    /**
     * @response array{
     *     id: int,
     *     uuid: string,
     *     radical: string|null,
     *     strokes: int|null,
     *     meaning: string|null,
     *     hiragana: string|null,
     *     kanjis: array<int, KanjiResource>
     * }
     */
    #[Response(type: 'array{id: int, uuid: string, radical: string|null, strokes: int|null, meaning: string|null, hiragana: string|null, kanjis: array<int, KanjiResource>}')]
    public function show(string $identifier): JsonResponse|JsonResource
    {
        $result = $this->radicalService->findByIdentifier($identifier, withKanjis: true);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new RadicalResource($result->getData(), includeKanjis: true);
    }
}
