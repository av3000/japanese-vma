<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Controllers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceWriteDTO;
use App\Domain\JapaneseMaterial\Sentences\Errors\SentenceErrors;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Sentences\Requests\IndexSentenceRequest;
use App\Http\v1\JapaneseMaterial\Sentences\Requests\StoreSentenceRequest;
use App\Http\v1\JapaneseMaterial\Sentences\Requests\UpdateSentenceRequest;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceListResource;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceController extends Controller
{
    public function __construct(
        private readonly SentenceServiceInterface $sentenceService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

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

    // Detail, create and update all return `SentenceResource`, so they all
    // reference the one named component. An inline shape here would be a
    // second description of the same class, free to drift from it silently.
    #[Response(type: 'SentenceResource')]
    public function show(string $identifier): JsonResponse|JsonResource
    {
        $result = $this->sentenceService->findByIdentifier($identifier, withKanjis: true, withWords: true);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new SentenceResource($result->getData(), includeKanjis: true, includeWords: true);
    }

    // Scramble documents an inferred `200` beside this `201` because the action
    // returns a JsonResponse, and Orval turns the two success responses into
    // `string | SentenceResource`. The seam in client/src/api/sentences/authoring.ts
    // narrows that at runtime; see the note there. Attempts that did not help:
    // widening the return type to `JsonResponse|JsonResource`, `response()->json(…, 201)`,
    // and returning a bare resource — Scramble only replaces an inferred response
    // when the attribute declares the *same* status.
    #[Response(201, type: 'SentenceResource')]
    public function store(StoreSentenceRequest $request): JsonResponse
    {
        $result = $this->sentenceService->create(
            SentenceWriteDTO::fromValidated($request->validated()),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return (new SentenceResource($result->getData(), includeKanjis: true, includeWords: true))
            ->response()
            ->setStatusCode(201);
    }

    #[Response(type: 'SentenceResource')]
    public function update(UpdateSentenceRequest $request, string $uuid): JsonResponse|JsonResource
    {
        if (! EntityId::isValid($uuid)) {
            return TypedResults::fromError(SentenceErrors::invalidIdentifier());
        }

        $result = $this->sentenceService->update(
            EntityId::from($uuid),
            SentenceWriteDTO::fromValidated($request->validated()),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new SentenceResource($result->getData(), includeKanjis: true, includeWords: true);
    }

    #[Response(204)]
    public function destroy(string $uuid): JsonResponse
    {
        if (! EntityId::isValid($uuid)) {
            return TypedResults::fromError(SentenceErrors::invalidIdentifier());
        }

        $result = $this->sentenceService->delete(
            EntityId::from($uuid),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::noContent();
    }

    private function requiredAuthenticatedUser(): AuthenticatedUser
    {
        return $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;
    }
}
