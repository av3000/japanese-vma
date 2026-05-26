<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Controllers;

use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\JapaneseMaterial\Sentences\Requests\IndexSentenceRequest;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceListResource;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceController extends Controller
{
    public function __construct(
        private readonly SentenceServiceInterface $sentenceService,
    ) {}

    /**
     * @response array{
     *     items: array<int, array{
     *         id: int,
     *         uuid: string,
     *         user_id: int|null,
     *         tatoeba_entry: string|null,
     *         content: string
     *     }>,
     *     pagination: PaginationResource
     * }
     */
    #[Response(type: 'array{items: array<int, array{id: int, uuid: string, user_id: int|null, tatoeba_entry: string|null, content: string}>, pagination: PaginationResource}')]
    public function index(IndexSentenceRequest $request): JsonResponse|JsonResource
    {
        $validated = $request->validated();

        $criteria = SentenceQueryCriteria::forListing(
            page: $validated['page'] ?? Pagination::MIN_PAGE,
            perPage: $validated['per_page'] ?? SentenceQueryCriteria::DEFAULT_PER_PAGE,
            keyword: $validated['keyword'] ?? null,
            content: $validated['content'] ?? null,
            tatoebaEntry: $validated['tatoeba_entry'] ?? null,
            userId: $validated['user_id'] ?? null,
        );

        $result = $this->sentenceService->find($criteria);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new SentenceListResource($result->getData());
    }

    /**
     * @response array{
     *     id: int,
     *     uuid: string,
     *     user_id: int|null,
     *     tatoeba_entry: string|null,
     *     content: string,
     *     kanjis: array<int, KanjiResource>,
     *     words: array<int, mixed>
     * }
     */
    #[Response(type: 'array{id: int, uuid: string, user_id: int|null, tatoeba_entry: string|null, content: string, kanjis: array<int, KanjiResource>, words: array<int, mixed>}')]
    public function show(string $identifier): JsonResponse|JsonResource
    {
        $result = $this->sentenceService->findByIdentifier($identifier, withKanjis: true);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new SentenceResource($result->getData(), includeKanjis: true, includeWords: true);
    }
}
