<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\Models\Flashcard;
use App\Mcp\Presenters\FlashcardPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlashcardPresenterTest extends TestCase
{
    public function test_a_card_has_the_documented_keys_and_none_of_the_internal_ones(): void
    {
        $card = $this->card(
            promptText: '亜',
            promptHint: null,
            acceptedAnswers: ['Asia', 'rank next'],
            displayAnswer: 'Asia',
            options: ['rank next', 'Asia', 'mute', 'study'],
            jlpt: '1',
        );

        $this->assertSame(
            [
                'prompt' => '亜',
                'hint' => null,
                'answer' => 'Asia',
                'accepted_answers' => ['Asia', 'rank next'],
                'options' => ['rank next', 'Asia', 'mute', 'study'],
                'level' => 'N1',
            ],
            (new FlashcardPresenter())->presentCard($card),
        );
    }

    public function test_typed_cards_have_no_options_and_a_word_card_keeps_its_hint(): void
    {
        $presented = (new FlashcardPresenter())->presentCard($this->card(promptHint: 'noun (common) (futsuumeishi)', options: null));

        $this->assertNull($presented['options']);
        $this->assertSame('noun (common) (futsuumeishi)', $presented['hint']);
    }

    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function storedLevels(): array
    {
        return [
            'the easiest level' => ['5', 'N5'],
            'the hardest level' => ['1', 'N1'],
            'no level' => [null, null],
            'a level that does not exist' => ['9', null],
            'the dictionary marker for no level' => ['-', null],
        ];
    }

    #[DataProvider('storedLevels')]
    public function test_the_level_is_read_as_n5_to_n1_or_null(?string $stored, ?string $expected): void
    {
        $this->assertSame($expected, (new FlashcardPresenter())->presentCard($this->card(jlpt: $stored))['level']);
    }

    /**
     * @param array<int, string> $acceptedAnswers
     * @param array<int, string>|null $options
     */
    private function card(
        string $promptText = '学校',
        ?string $promptHint = null,
        array $acceptedAnswers = ['school'],
        string $displayAnswer = 'school',
        ?array $options = null,
        ?string $jlpt = null,
    ): Flashcard {
        return new Flashcard(
            itemId: 7,
            itemUuid: new EntityId('0b0a7c06-1f0f-4d0e-9f9c-5c3b6c9a1d11'),
            promptText: $promptText,
            promptHint: $promptHint,
            acceptedAnswers: $acceptedAnswers,
            displayAnswer: $displayAnswer,
            options: $options,
            jlpt: $jlpt,
            grade: '1',
            strokes: 8,
        );
    }
}
