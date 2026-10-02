<?php

declare(strict_types=1);

namespace App\Application\Study\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories\RadicalRepositoryInterface;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Domain\Study\Errors\StudyErrors;
use App\Domain\Study\Factories\FlashcardFactory;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Shared\Results\Result;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class FlashcardDeckService implements FlashcardDeckServiceInterface
{
    public function __construct(
        private readonly CatalogueServiceInterface $catalogueService,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly KanjiRepositoryInterface $kanjiRepository,
        private readonly WordRepositoryInterface $wordRepository,
        private readonly RadicalRepositoryInterface $radicalRepository,
        private readonly FlashcardFactory $flashcardFactory,
    ) {
    }

    public function buildDeck(EntityId $catalogueUuid, FlashcardConfig $config, ?AuthenticatedUser $viewer): Result
    {
        $catalogueResult = $this->catalogueService->getViewableCatalogue($catalogueUuid, $viewer);

        if ($catalogueResult->isFailure()) {
            return $catalogueResult;
        }

        /** @var Catalogue $catalogue */
        $catalogue = $catalogueResult->getData();
        $type = $catalogue->getType();

        if (! FlashcardConfig::supportsType($type)) {
            return Result::failure(StudyErrors::catalogueTypeNotSupported($catalogueUuid->value(), $type->label()));
        }

        if (! $config->isValidFor($type)) {
            return Result::failure(StudyErrors::invalidFieldCombination(
                $catalogueUuid->value(),
                $config->prompt->value,
                $config->answer->value,
                $config->mode->value,
            ));
        }

        $itemIds = $this->catalogueItemRepository->findItemIdsByCatalogueId($catalogue->getIdValue());
        $cards = $this->mapCards($type, $itemIds, $config);

        if ($cards === []) {
            return Result::failure(StudyErrors::noEligibleCards($catalogueUuid->value(), $config->answer->value));
        }

        $randomizer = new Randomizer(new Mt19937($config->seed));
        $deck = array_slice($randomizer->shuffleArray($cards), 0, $config->count);

        return Result::success(new FlashcardDeckDTO(
            catalogue: $catalogue,
            config: $config,
            cards: array_values($deck),
            totalItems: count($itemIds),
            eligibleItems: count($cards),
            excludedEmptyAnswerField: count($itemIds) - count($cards),
        ));
    }

    /**
     * @param int[] $itemIds
     *
     * @return list<Flashcard>
     */
    private function mapCards(SavedListType $type, array $itemIds, FlashcardConfig $config): array
    {
        if ($itemIds === []) {
            return [];
        }

        $cards = match (FlashcardConfig::baseType($type)) {
            SavedListType::KANJIS => array_map(
                fn (Kanji $kanji): ?Flashcard => $this->flashcardFactory->fromKanji($kanji, $config),
                $this->kanjiRepository->findByIds($itemIds),
            ),
            SavedListType::WORDS => array_map(
                fn (Word $word): ?Flashcard => $this->flashcardFactory->fromWord($word, $config),
                $this->wordRepository->findByIds($itemIds),
            ),
            SavedListType::RADICALS => array_map(
                fn (Radical $radical): ?Flashcard => $this->flashcardFactory->fromRadical($radical, $config),
                $this->radicalRepository->findByIds($itemIds),
            ),
            default => [],
        };

        return array_values(array_filter($cards));
    }
}
