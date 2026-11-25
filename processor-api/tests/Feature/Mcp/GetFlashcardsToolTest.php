<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Study\Services\FlashcardDeckServiceInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use App\Mcp\Servers\StudyServer;
use App\Mcp\Tools\GetFlashcardsTool;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use App\Shared\Results\ResultError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

final class GetFlashcardsToolTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const NOT_FOUND = 'No public list with that id. Use find_decks to get one.';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->owner = User::factory()->create();
    }

    // ------------------------------------------------------------------ AC-1

    public function test_the_tool_is_read_only_and_takes_the_documented_inputs(): void
    {
        $tool = app(GetFlashcardsTool::class)->toArray();

        $this->assertSame('get_flashcards', $tool['name']);
        $this->assertTrue($tool['annotations']['readOnlyHint']);

        $input = $tool['inputSchema'];
        $this->assertSame(['deck', 'count', 'prompt', 'answer', 'mode', 'seed'], array_keys($input['properties']));
        $this->assertSame(['deck'], $input['required']);
        $this->assertSame(1, $input['properties']['count']['minimum']);
        $this->assertSame(30, $input['properties']['count']['maximum']);
        $this->assertSame(10, $input['properties']['count']['default']);
        $this->assertSame(['character', 'meaning', 'onyomi', 'kunyomi', 'reading'], $input['properties']['prompt']['enum']);
        $this->assertSame(['character', 'meaning', 'onyomi', 'kunyomi', 'reading'], $input['properties']['answer']['enum']);
        $this->assertSame('character', $input['properties']['prompt']['default']);
        $this->assertSame('meaning', $input['properties']['answer']['default']);
        $this->assertSame(['typed', 'options'], $input['properties']['mode']['enum']);
        $this->assertSame('typed', $input['properties']['mode']['default']);
        $this->assertSame(0, $input['properties']['seed']['minimum']);
        $this->assertSame(2147483647, $input['properties']['seed']['maximum']);
    }

    public function test_the_defaults_are_a_character_to_meaning_typed_deck_of_ten(): void
    {
        $deck = $this->kanjiDeck(12);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid])
            ->assertOk()
            ->assertStructuredContentCount(10, 'cards')
            ->assertStructuredContentPaths([
                'config.prompt' => 'character',
                'config.answer' => 'meaning',
                'config.mode' => 'typed',
                'config.count' => 10,
                'total_items' => 12,
                'eligible_items' => 12,
            ]);
    }

    public function test_the_script_is_lenient_so_either_kana_script_is_accepted(): void
    {
        $this->mock(FlashcardDeckServiceInterface::class)
            ->shouldReceive('buildDeck')
            ->once()
            ->withArgs(fn (EntityId $uuid, FlashcardConfig $config, ?AuthenticatedUser $viewer): bool => $config->question->script === ScriptStrictness::LENIENT && $viewer === null)
            ->andReturn(Result::failure(new ResultError('Study.NoEligibleCards', HttpStatus::UNPROCESSABLE_ENTITY, 'No eligible cards')));

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => (string) Str::uuid(), 'answer' => 'onyomi'])
            ->assertHasErrors(['No eligible cards']);
    }

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>}>
     */
    public static function outOfRangeInput(): array
    {
        return [
            'a count of 0' => [['count' => 0], ['count', '1 to 30']],
            'a count of 31' => [['count' => 31], ['count', '1 to 30']],
            'a count that is not a number' => [['count' => 'ten'], ['count', '1 to 30']],
            'a count with a fraction' => [['count' => 2.5], ['count', '1 to 30']],
            'a prompt that is not a field' => [['prompt' => 'furigana'], ['prompt', 'character, meaning, onyomi, kunyomi, reading']],
            'a prompt in capitals' => [['prompt' => 'MEANING'], ['prompt', 'one of']],
            'a prompt that is not text' => [['prompt' => ['meaning']], ['prompt', 'one of']],
            'an answer that is not a field' => [['answer' => 'nonsense'], ['answer', 'character, meaning, onyomi, kunyomi, reading']],
            'a mode that is not offered' => [['mode' => 'oral'], ['mode', 'typed or options']],
            'a seed below 0' => [['seed' => -1], ['seed', '0 to 2147483647']],
            'a seed over the maximum' => [['seed' => 2147483648], ['seed', '0 to 2147483647']],
            'a seed that is not a number' => [['seed' => 'abc'], ['seed', '0 to 2147483647']],
            'a huge seed' => [['seed' => PHP_INT_MAX], ['seed', '0 to 2147483647']],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<int, string> $expectedInMessage
     */
    #[DataProvider('outOfRangeInput')]
    public function test_a_value_out_of_range_is_an_error_naming_the_field_and_no_deck_is_built(
        array $arguments,
        array $expectedInMessage,
    ): void {
        $this->mock(FlashcardDeckServiceInterface::class)->shouldNotReceive('buildDeck');

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => (string) Str::uuid(), ...$arguments])
            ->assertHasErrors($expectedInMessage);
    }

    public function test_the_edges_of_every_range_are_accepted(): void
    {
        $deck = $this->kanjiDeck(3);

        foreach ([['count' => 1, 'seed' => 0], ['count' => 30, 'seed' => 2147483647]] as $arguments) {
            StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, ...$arguments])
                ->assertOk()
                ->assertStructuredContentPaths(['config.count' => $arguments['count'], 'config.seed' => $arguments['seed']]);
        }
    }

    // ------------------------------------------------------------------ AC-2

    public function test_a_kanji_deck_has_the_documented_shape_without_internal_keys(): void
    {
        $deck = $this->createList(SavedListType::KANJIS, ['title' => 'N5 kanji']);
        $this->addItems($deck, SavedListType::KANJIS, [$this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|rank next|', '1')]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'seed' => 7])
            ->assertOk()
            ->assertStructuredContent([
                'deck' => ['uuid' => $deck->uuid, 'title' => 'N5 kanji', 'kind' => 'kanji'],
                'config' => ['prompt' => 'character', 'answer' => 'meaning', 'mode' => 'typed', 'count' => 10, 'seed' => 7],
                'cards' => [[
                    'prompt' => '亜',
                    'hint' => null,
                    'answer' => 'Asia',
                    'accepted_answers' => ['Asia', 'rank next'],
                    'options' => null,
                    'level' => 'N1',
                ]],
                'total_items' => 1,
                'eligible_items' => 1,
            ])
            ->assertStructuredContentMissingPaths([
                'cards.0.item_id',
                'cards.0.item_uuid',
                'cards.0.display_answer',
                'cards.0.meta',
                'config.script',
                'excluded',
                'catalogue',
                'deck.type',
            ]);
    }

    public function test_the_declared_output_schema_matches_the_keys_of_the_answer(): void
    {
        $output = app(GetFlashcardsTool::class)->toArray()['outputSchema'];

        $this->assertSame(['deck', 'config', 'cards', 'total_items', 'eligible_items'], array_keys($output['properties']));
        $this->assertSame(['uuid', 'title', 'kind'], array_keys($output['properties']['deck']['properties']));
        $this->assertSame(['prompt', 'answer', 'mode', 'count', 'seed'], array_keys($output['properties']['config']['properties']));
        $this->assertSame(
            ['prompt', 'hint', 'answer', 'accepted_answers', 'options', 'level'],
            array_keys($output['properties']['cards']['items']['properties']),
        );
    }

    public function test_a_word_deck_carries_the_word_type_as_hint_and_the_level_from_factory_data(): void
    {
        $deck = $this->createList(SavedListType::WORDS, ['title' => 'Words']);
        $this->addItems($deck, SavedListType::WORDS, [
            $this->insertWord('学校', 'がっこう', ['school', 'academy'], '5'),
        ]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'answer' => 'reading'])
            ->assertOk()
            ->assertStructuredContentPaths([
                'deck.kind' => 'words',
                'cards.0.prompt' => '学校',
                'cards.0.hint' => 'noun (common) (futsuumeishi)',
                'cards.0.answer' => 'がっこう',
                'cards.0.accepted_answers' => ['がっこう'],
                'cards.0.level' => 'N5',
            ]);
    }

    public function test_a_word_without_a_level_has_a_null_level(): void
    {
        // No dictionary word has a level until #399, so this is every word today.
        $deck = $this->createList(SavedListType::WORDS);
        $this->addItems($deck, SavedListType::WORDS, [$this->insertWord('学校', 'がっこう', ['school'], '-')]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid])
            ->assertOk()
            ->assertStructuredContentPaths(['cards.0.level' => null]);
    }

    public function test_a_radical_deck_accepts_both_readings_and_has_no_level(): void
    {
        $deck = $this->createList(SavedListType::RADICALS);
        $this->addItems($deck, SavedListType::RADICALS, [$this->insertRadical('一', 'one', 'いち / ichi')]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'answer' => 'reading'])
            ->assertOk()
            ->assertStructuredContentPaths([
                'deck.kind' => 'radicals',
                'cards.0.prompt' => '一',
                'cards.0.hint' => null,
                'cards.0.answer' => 'いち',
                'cards.0.accepted_answers' => ['いち', 'ichi'],
                'cards.0.level' => null,
            ]);
    }

    public function test_a_prompt_can_be_the_meaning_with_the_character_as_options(): void
    {
        $deck = $this->kanjiDeck(5);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'prompt' => 'meaning', 'answer' => 'character', 'mode' => 'options', 'count' => 5, 'seed' => 3])
            ->assertOk()
            ->assertStructuredContentPaths(['config.prompt' => 'meaning', 'config.answer' => 'character', 'config.mode' => 'options'])
            ->assertStructuredContent(function (AssertableJson $json): void {
                $cards = $json->toArray()['cards'];
                $this->assertCount(5, $cards);

                foreach ($cards as $card) {
                    $this->assertCount(4, $card['options']);
                    $this->assertContains($card['answer'], $card['options']);
                }

                $json->etc();
            });
    }

    public function test_options_mode_gives_four_options_and_typed_mode_gives_none(): void
    {
        $deck = $this->kanjiDeck(6);

        $options = $this->callStructured(['deck' => $deck->uuid, 'mode' => 'options', 'seed' => 9]);
        $typed = $this->callStructured(['deck' => $deck->uuid, 'mode' => 'typed', 'seed' => 9]);

        foreach ($options['cards'] as $card) {
            $this->assertCount(4, $card['options']);
            $this->assertContains($card['answer'], $card['options']);
        }

        foreach ($typed['cards'] as $card) {
            $this->assertNull($card['options']);
        }
    }

    public function test_items_without_an_answer_are_left_out_and_counted_in_the_difference(): void
    {
        $deck = $this->createList(SavedListType::KANJIS);
        $this->addItems($deck, SavedListType::KANJIS, [
            $this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|'),
            $this->insertKanji('唖', 'ア|アク|', '-', 'mute|'),
        ]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'answer' => 'kunyomi'])
            ->assertOk()
            ->assertStructuredContentCount(1, 'cards')
            ->assertStructuredContentPaths(['total_items' => 2, 'eligible_items' => 1, 'cards.0.prompt' => '亜']);
    }

    public function test_a_known_list_reports_the_kind_of_the_things_it_holds(): void
    {
        $deck = $this->createList(SavedListType::KNOWNKANJIS);
        $this->addItems($deck, SavedListType::KNOWNKANJIS, [$this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|')]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid])
            ->assertOk()
            ->assertStructuredContentPaths(['deck.kind' => 'kanji', 'cards.0.answer' => 'Asia']);
    }

    // ------------------------------------------------------------------ AC-3

    /**
     * @return array<string, array{string}>
     */
    public static function modes(): array
    {
        return ['typed' => ['typed'], 'options' => ['options']];
    }

    #[DataProvider('modes')]
    public function test_the_same_seed_returns_the_same_cards_in_the_same_order(string $mode): void
    {
        $deck = $this->kanjiDeck(12);
        $arguments = ['deck' => $deck->uuid, 'count' => 8, 'mode' => $mode, 'seed' => 42];

        $first = $this->callStructured($arguments);
        $second = $this->callStructured($arguments);

        $this->assertSame($first, $second);
        $this->assertCount(8, $first['cards']);
        $this->assertSame(42, $first['config']['seed']);
    }

    public function test_another_seed_deals_another_order(): void
    {
        $deck = $this->kanjiDeck(12);

        $orders = [];

        foreach ([1, 2, 3, 4] as $seed) {
            $orders[] = array_column($this->callStructured(['deck' => $deck->uuid, 'count' => 12, 'seed' => $seed])['cards'], 'prompt');
        }

        $this->assertGreaterThan(1, count(array_unique(array_map('json_encode', $orders))));
    }

    public function test_a_call_without_a_seed_returns_the_seed_it_used_and_replaying_it_gives_the_same_deck(): void
    {
        $deck = $this->kanjiDeck(12);

        $first = $this->callStructured(['deck' => $deck->uuid, 'count' => 8, 'mode' => 'options']);

        $this->assertIsInt($first['config']['seed']);
        $this->assertGreaterThanOrEqual(0, $first['config']['seed']);
        $this->assertLessThanOrEqual(2147483647, $first['config']['seed']);

        $replay = $this->callStructured(['deck' => $deck->uuid, 'count' => 8, 'mode' => 'options', 'seed' => $first['config']['seed']]);

        $this->assertSame($first, $replay);
    }

    // ------------------------------------------------------------------ AC-4

    public function test_the_same_field_as_prompt_and_answer_is_an_error_naming_the_rule_and_no_deck_is_built(): void
    {
        $this->mock(FlashcardDeckServiceInterface::class)->shouldNotReceive('buildDeck');

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => (string) Str::uuid(), 'prompt' => 'meaning', 'answer' => 'meaning'])
            ->assertHasErrors(['answer', 'must differ from prompt']);
    }

    public function test_a_typed_character_answer_is_an_error_naming_the_rule_and_no_deck_is_built(): void
    {
        $this->mock(FlashcardDeckServiceInterface::class)->shouldNotReceive('buildDeck');

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => (string) Str::uuid(), 'prompt' => 'meaning', 'answer' => 'character'])
            ->assertHasErrors(['mode', 'character answer cannot be typed', 'options']);
    }

    public function test_a_character_answer_is_fine_in_options_mode(): void
    {
        $deck = $this->kanjiDeck(5);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'prompt' => 'meaning', 'answer' => 'character', 'mode' => 'options'])
            ->assertOk();
    }

    public function test_a_character_for_both_sides_reports_both_rules_without_throwing(): void
    {
        $this->mock(FlashcardDeckServiceInterface::class)->shouldNotReceive('buildDeck');

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => (string) Str::uuid(), 'prompt' => 'character', 'answer' => 'character'])
            ->assertHasErrors(['answer', 'must differ from prompt', 'mode', 'cannot be typed']);
    }

    /**
     * @return array<string, array{SavedListType, string, string}>
     */
    public static function fieldsTheKindLacks(): array
    {
        return [
            'onyomi on a words deck' => [SavedListType::WORDS, 'onyomi', 'meaning'],
            'kunyomi on a radicals deck' => [SavedListType::RADICALS, 'character', 'kunyomi'],
            'reading on a kanji deck' => [SavedListType::KANJIS, 'character', 'reading'],
            'a meaning to onyomi card, neither side a character' => [SavedListType::KANJIS, 'meaning', 'onyomi'],
        ];
    }

    #[DataProvider('fieldsTheKindLacks')]
    public function test_a_field_the_kind_does_not_have_returns_the_deck_services_reason(SavedListType $type, string $prompt, string $answer): void
    {
        $deck = $this->createList($type);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid, 'prompt' => $prompt, 'answer' => $answer])
            ->assertHasErrors(["Catalogue {$deck->uuid} cannot be studied with prompt {$prompt}, answer {$answer}"]);
    }

    public function test_a_list_that_cannot_be_studied_returns_the_deck_services_reason(): void
    {
        $deck = $this->createList(SavedListType::SENTENCES);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid])
            ->assertHasErrors(['only kanji, words and radicals catalogues can be studied']);
    }

    public function test_a_list_with_nothing_to_answer_with_returns_the_deck_services_reason(): void
    {
        $deck = $this->createList(SavedListType::KANJIS);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $deck->uuid])
            ->assertHasErrors(['has a meaning to answer with']);
    }

    // ------------------------------------------------------------------ AC-5 and epic AC-3

    /**
     * @return array<string, array{SavedListType}>
     */
    public static function kinds(): array
    {
        return [
            'kanji' => [SavedListType::KANJIS],
            'words' => [SavedListType::WORDS],
            'radicals' => [SavedListType::RADICALS],
        ];
    }

    #[DataProvider('kinds')]
    public function test_a_private_deck_is_an_error_with_no_cards_while_a_public_one_beside_it_works(SavedListType $type): void
    {
        $public = $this->createList($type, ['title' => 'Public list']);
        $private = $this->createList($type, ['title' => 'Private secret list'], private: true);

        foreach ([$public, $private] as $deck) {
            $this->addItems($deck, $type, [$this->insertItem($type, 1), $this->insertItem($type, 2)]);
        }

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $public->uuid, 'prompt' => 'meaning', 'answer' => 'character', 'mode' => 'options'])
            ->assertOk()
            ->assertStructuredContentCount(2, 'cards');

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $private->uuid, 'prompt' => 'meaning', 'answer' => 'character', 'mode' => 'options'])
            ->assertHasErrors([self::NOT_FOUND])
            ->assertDontSee(['Private secret list', 'cards']);
    }

    public function test_a_private_deck_and_a_missing_deck_give_the_same_answer(): void
    {
        $private = $this->createList(SavedListType::KANJIS, [], private: true);
        $this->addItems($private, SavedListType::KANJIS, [$this->insertKanji('亜', 'ア|', 'つ.ぐ|', 'Asia|')]);

        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $private->uuid])->assertHasErrors([self::NOT_FOUND]);
        StudyServer::tool(GetFlashcardsTool::class, ['deck' => (string) Str::uuid()])->assertHasErrors([self::NOT_FOUND]);
    }

    public function test_a_private_deck_is_hidden_before_its_fields_are_judged(): void
    {
        $private = $this->createList(SavedListType::WORDS, [], private: true);

        // The same question that tells a public deck "cannot be studied with prompt onyomi" must not say so for a private one.
        StudyServer::tool(GetFlashcardsTool::class, ['deck' => $private->uuid, 'prompt' => 'character', 'answer' => 'onyomi'])
            ->assertHasErrors([self::NOT_FOUND]);
    }

    public function test_a_private_deck_of_another_kind_in_the_database_does_not_change_a_public_decks_result(): void
    {
        $public = $this->kanjiDeck(3);
        $private = $this->createList(SavedListType::KANJIS, ['title' => 'Hidden'], private: true);
        $this->addItems($private, SavedListType::KANJIS, [$this->insertKanji('秘', 'ヒ|', 'ひ.める|', 'secret|')]);

        $response = $this->callStructured(['deck' => $public->uuid, 'count' => 30, 'seed' => 5]);

        $this->assertSame(3, $response['total_items']);
        $this->assertNotContains('秘', array_column($response['cards'], 'prompt'));
    }

    // ------------------------------------------------------------------ AC-6

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function malformedDecks(): array
    {
        return [
            'no deck' => [[]],
            'a null deck' => [['deck' => null]],
            'an empty deck' => [['deck' => '']],
            'a deck that is not a uuid' => [['deck' => 'abc']],
            'a numeric id' => [['deck' => 123]],
            'a uuid with a letter too many' => [['deck' => '0b0a7c06-1f0f-4d0e-9f9c-5c3b6c9a1d11a']],
            'a deck that is not text' => [['deck' => ['0b0a7c06-1f0f-4d0e-9f9c-5c3b6c9a1d11']]],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('malformedDecks')]
    public function test_a_malformed_deck_is_an_error_naming_the_field_and_nothing_is_logged(array $arguments): void
    {
        $this->mock(FlashcardDeckServiceInterface::class)->shouldNotReceive('buildDeck');
        Log::spy();

        StudyServer::tool(GetFlashcardsTool::class, $arguments)->assertHasErrors(['deck', 'uuid of a list']);

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('critical');
        Log::shouldNotHaveReceived('warning');
    }

    // ------------------------------------------------------------------ AC-7

    public function test_the_description_tells_the_assistant_to_ask_one_card_at_a_time_and_keep_the_answer_back(): void
    {
        $description = app(GetFlashcardsTool::class)->toArray()['description'];

        $this->assertStringContainsString('one card at a time', $description);
        $this->assertStringContainsString('before the learner replies', $description);
        $this->assertStringContainsString('find_decks', $description);
        $this->assertStringContainsString('seed', $description);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function callStructured(array $arguments): array
    {
        $captured = [];

        $this->asserted(StudyServer::tool(GetFlashcardsTool::class, $arguments), $captured);

        return $captured;
    }

    /**
     * @param array<string, mixed> $captured
     */
    private function asserted(TestResponse $response, array &$captured): void
    {
        $response->assertOk()->assertStructuredContent(function (AssertableJson $json) use (&$captured): void {
            $captured = $json->toArray();
            $json->etc();
        });
    }

    private function kanjiDeck(int $items): Catalogue
    {
        $deck = $this->createList(SavedListType::KANJIS, ['title' => 'Kanji drill']);

        $this->addItems($deck, SavedListType::KANJIS, array_map(
            fn (int $number): int => $this->insertKanji($this->kanjiNumber($number), 'ジ|', 'あざ|', "meaning number {$number}|"),
            range(1, $items),
        ));

        return $deck;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createList(SavedListType $type, array $attributes = [], bool $private = false): Catalogue
    {
        $factory = Catalogue::factory()->byUser($this->owner)->ofType($type);

        if ($private) {
            $factory = $factory->asPrivate();
        }

        return $factory->create($attributes);
    }

    /**
     * @param array<int, int> $itemIds
     */
    private function addItems(Catalogue $list, SavedListType $type, array $itemIds): void
    {
        foreach ($itemIds as $itemId) {
            DB::table('customlist_object')->insert([
                'list_id' => $list->id,
                'listtype_id' => $type->value,
                'real_object_id' => $itemId,
            ]);
        }
    }

    private function insertItem(SavedListType $type, int $number): int
    {
        return match ($type) {
            SavedListType::KANJIS => $this->insertKanji($this->kanjiNumber($number), 'ジ|', 'あざ|', "meaning {$number}|"),
            SavedListType::WORDS => $this->insertWord("語{$number}", "ご{$number}", ["gloss {$number}"]),
            default => $this->insertRadical("部{$number}", "meaning {$number}", "ぶ / bu {$number}"),
        };
    }

    /** A kanji has to be one character, so the nth drill kanji is the nth character of a fixed run of them. */
    private function kanjiNumber(int $number): string
    {
        return mb_substr('一二三四五六七八九十日月火水木金土山川田人口目耳手足力大小中', $number - 1, 1);
    }

    private function insertKanji(string $kanji, string $onyomi, string $kunyomi, string $meaning, string $jlpt = '5'): int
    {
        return (int) DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => $onyomi,
            'kunyomi' => $kunyomi,
            'meaning' => $meaning,
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => 8,
            'jlpt' => $jlpt,
            'frequency' => '100',
            'radicals' => '-',
            'radical_parts' => '-',
        ]);
    }

    /**
     * @param list<string> $glosses
     */
    private function insertWord(string $word, string $furigana, array $glosses, string $jlpt = '-'): int
    {
        return (int) DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1000000',
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => $jlpt,
            'word_type' => 'noun (common) (futsuumeishi)|',
            'word_k_ele' => '[]',
            'furigana_r_ele' => '[]',
            'sense' => json_encode([[['gloss', $glosses]]], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function insertRadical(string $radical, string $meaning, string $hiragana): int
    {
        return (int) DB::table('japanese_radicals_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'radical' => $radical,
            'strokes' => 1,
            'meaning' => $meaning,
            'hiragana' => $hiragana,
        ]);
    }
}
