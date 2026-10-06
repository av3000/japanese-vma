<?php

declare(strict_types=1);

namespace App\Application\Study\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Shared\Results\Result;

interface FlashcardDeckServiceInterface
{
    /**
     * Read a catalogue as a deck for the given configuration.
     *
     * Visibility follows the catalogue: public for anyone, private for the owner or an
     * admin. Failures: Catalogues.NotFound, Catalogues.AccessDenied,
     * Study.CatalogueTypeNotSupported, Study.InvalidFieldCombination, Study.NoEligibleCards.
     *
     * @return Result Success data: FlashcardDeckDTO.
     */
    public function buildDeck(EntityId $catalogueUuid, FlashcardConfig $config, ?AuthenticatedUser $viewer): Result;
}
