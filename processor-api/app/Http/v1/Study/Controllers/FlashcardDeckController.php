<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Study\Services\FlashcardDeckServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Http\Controllers\Controller;
use App\Http\v1\Study\Requests\FlashcardDeckRequest;
use App\Http\v1\Study\Resources\FlashcardDeckResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardDeckController extends Controller
{
    public function __construct(
        private readonly FlashcardDeckServiceInterface $flashcardDeckService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Read a catalogue as a flashcard deck.
     *
     * Public for public catalogues; private catalogues answer 403 to anyone but the owner
     * or an admin. The deck is shuffled with `seed` and cut to `count`; the same seed
     * reproduces the same deck, which is how "retry missed" works.
     *
     * @response FlashcardDeckResource
     */
    #[Response(type: 'FlashcardDeckResource')]
    public function show(string $uuid, FlashcardDeckRequest $request): JsonResponse|JsonResource
    {
        $result = $this->flashcardDeckService->buildDeck(
            EntityId::from($uuid),
            $request->toConfig(),
            $this->currentUserProvider->currentAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var FlashcardDeckDTO $deck */
        $deck = $result->getData();

        return new FlashcardDeckResource($deck);
    }
}
