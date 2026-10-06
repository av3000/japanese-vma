<?php

declare(strict_types=1);

namespace App\Application\Study\Actions;

use App\Application\Study\Interfaces\Readers\DistractorPoolReaderInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\DTOs\EligibleCardsDTO;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\Support\AnswerNormalizer;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Gives every card in options mode exactly OPTION_COUNT choices: its own display answer
 * plus distractors.
 *
 * Distractors come from the catalogue's other eligible cards first (all of them, not only
 * the cut), and only cards whose accepted answers share nothing with the correct card's
 * after normalization: 学 and 習 both mean "learn", so neither may be the other's wrong
 * answer. When the catalogue cannot supply three, the dictionary pool fills in, preferring
 * the card's JLPT level or stroke count. Pools are fetched once per level and reused across
 * cards, so the number of queries is bounded by the number of distinct levels in the deck,
 * not by its size. Candidate order, pool order and option order all follow the seed.
 */
final class BuildDistractorsAction
{
    public const OPTION_COUNT = 4;

    private const POOL_SIZE = 24;

    public function __construct(
        private readonly DistractorPoolReaderInterface $distractorPool,
    ) {
    }

    /**
     * @param list<Flashcard> $deck The cards that will be served; a subset of `$source->cards`.
     *
     * @return list<Flashcard> The deck with `options` filled, same order.
     */
    public function execute(array $deck, EligibleCardsDTO $source, FlashcardQuestion $question, int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $answerSets = [];

        foreach ($source->cards as $card) {
            $answerSets[$card->itemId] = AnswerNormalizer::normalizeAll($card->acceptedAnswers, $question->answer);
        }

        /** @var array<string, list<Flashcard>> $pools */
        $pools = [];

        return array_map(function (Flashcard $card) use ($source, $answerSets, $question, $seed, $randomizer, &$pools): Flashcard {
            $correct = $answerSets[$card->itemId] ?? AnswerNormalizer::normalizeAll($card->acceptedAnswers, $question->answer);
            $used = [AnswerNormalizer::normalize($card->displayAnswer, $question->answer) => true];
            $distractors = [];

            $candidates = array_values(array_filter(
                $source->cards,
                static fn (Flashcard $other): bool => $other->itemId !== $card->itemId
                    && array_intersect($answerSets[$other->itemId] ?? [], $correct) === [],
            ));

            $this->take($randomizer->shuffleArray($candidates), $question, $used, $distractors);

            if (count($distractors) < self::OPTION_COUNT - 1) {
                $preferred = $this->pool($pools, $source, $question, $seed, $card->jlpt, $card->strokes);
                $this->take($this->disjoint($preferred, $correct, $question), $question, $used, $distractors);
            }

            if (count($distractors) < self::OPTION_COUNT - 1) {
                $any = $this->pool($pools, $source, $question, $seed, null, null);
                $this->take($this->disjoint($any, $correct, $question), $question, $used, $distractors);
            }

            return $card->withOptions($randomizer->shuffleArray([$card->displayAnswer, ...$distractors]));
        }, $deck);
    }

    /**
     * Appends candidates' display answers to `$distractors` until three are collected,
     * skipping any whose normalized text is already an option.
     *
     * @param list<Flashcard> $candidates
     * @param array<string, true> $used
     * @param list<string> $distractors
     */
    private function take(array $candidates, FlashcardQuestion $question, array &$used, array &$distractors): void
    {
        foreach ($candidates as $candidate) {
            if (count($distractors) >= self::OPTION_COUNT - 1) {
                return;
            }

            $key = AnswerNormalizer::normalize($candidate->displayAnswer, $question->answer);

            if ($key === '' || isset($used[$key])) {
                continue;
            }

            $used[$key] = true;
            $distractors[] = $candidate->displayAnswer;
        }
    }

    /**
     * @param list<Flashcard> $cards
     * @param list<string> $correct
     *
     * @return list<Flashcard>
     */
    private function disjoint(array $cards, array $correct, FlashcardQuestion $question): array
    {
        return array_values(array_filter(
            $cards,
            static fn (Flashcard $card): bool => array_intersect(
                AnswerNormalizer::normalizeAll($card->acceptedAnswers, $question->answer),
                $correct,
            ) === [],
        ));
    }

    /**
     * @param array<string, list<Flashcard>> $pools
     *
     * @return list<Flashcard>
     */
    private function pool(
        array &$pools,
        EligibleCardsDTO $source,
        FlashcardQuestion $question,
        int $seed,
        ?string $jlpt,
        ?int $strokes,
    ): array {
        $preferJlpt = $source->baseType === SavedListType::RADICALS ? null : $jlpt;
        $preferStrokes = $source->baseType === SavedListType::RADICALS ? $strokes : null;
        $key = ($preferJlpt ?? '').'|'.($preferStrokes ?? '');

        if (! array_key_exists($key, $pools)) {
            $pools[$key] = $this->distractorPool->sample(
                $source->baseType,
                $question,
                $seed,
                $source->catalogueItemIds,
                $preferJlpt,
                $preferStrokes,
                self::POOL_SIZE,
            );
        }

        return $pools[$key];
    }
}
