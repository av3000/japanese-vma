<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Controllers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Study\Services\StudySessionServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\StudyAttemptDTO;
use App\Domain\Study\DTOs\StudySessionCreateDTO;
use App\Domain\Study\Models\StudySession;
use App\Http\Controllers\Controller;
use App\Http\v1\Shared\Resources\UuidCreatedResource;
use App\Http\v1\Study\Requests\CompleteStudySessionRequest;
use App\Http\v1\Study\Requests\StoreStudyAttemptRequest;
use App\Http\v1\Study\Requests\StoreStudySessionRequest;
use App\Http\v1\Study\Resources\StudySessionResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class StudySessionController extends Controller
{
    public function __construct(
        private readonly StudySessionServiceInterface $studySessionService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Start a study session for a catalogue the caller may see.
     *
     * @response UuidCreatedResource
     */
    #[Response(201, type: 'UuidCreatedResource')]
    public function store(StoreStudySessionRequest $request): JsonResponse|JsonResource
    {
        $result = $this->studySessionService->createSession(
            StudySessionCreateDTO::fromValidated($request->validated()),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var StudySession $session */
        $session = $result->getData();

        return new UuidCreatedResource(['uuid' => $session->getUuid()->value()]);
    }

    /**
     * Record one answer. 201 when stored, 200 when the same `(item_id, attempt_no)` was
     * already recorded, so a retried request is harmless. 409 for a first-pass answer
     * (`attempt_no` 1) once the session is complete; "retry missed" rounds are still accepted.
     *
     * @response array{}
     */
    #[Response(201, type: 'array{}')]
    public function storeAttempt(string $uuid, StoreStudyAttemptRequest $request): JsonResponse
    {
        $result = $this->studySessionService->recordAttempt(
            EntityId::from($uuid),
            StudyAttemptDTO::fromValidated($request->validated()),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return response()->json([], $result->getData() === true ? 201 : 200);
    }

    /**
     * Close the session with its final score. Idempotent.
     *
     * @response StudySessionResource
     */
    #[Response(type: 'StudySessionResource')]
    public function complete(string $uuid, CompleteStudySessionRequest $request): JsonResponse|JsonResource
    {
        $result = $this->studySessionService->completeSession(
            EntityId::from($uuid),
            (int) $request->validated('correct_count'),
            $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var StudySession $session */
        $session = $result->getData();

        return new StudySessionResource($session);
    }

    private function requiredAuthenticatedUser(): AuthenticatedUser
    {
        return $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;
    }
}
