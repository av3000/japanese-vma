<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Application\Study\Services\FlashcardDeckServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use App\Mcp\Presenters\FlashcardPresenter;
use App\Mcp\Support\StudyLevel;
use App\Mcp\Support\ToolFailure;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_flashcards')]
#[Title('Get flashcards from a study list')]
#[Description(<<<'TEXT'
Get a set of flashcards from a public study list, by the uuid find_decks gave, to quiz the learner with.
Ask one card at a time. Never show a card's answer, accepted_answers or options before the learner replies: the answers are in this response so that you can check the reply, not to be read out.
Each card has prompt (what to show), hint (a word's type, or null), answer (the one answer to show after the reply), accepted_answers (every reply that counts as right), options (four choices when mode is options, otherwise null) and level (N5 to N1, or null).
prompt and answer are what a card shows and asks for: character, meaning, onyomi, kunyomi or reading. They must differ. By default a card shows the character and asks for the meaning. A kanji list has character, meaning, onyomi and kunyomi, a word or radical list has character, meaning and reading. One side must be the character.
mode typed (the default) has the learner type the answer; a character answer cannot be typed, so ask for it with mode options. Either kana script is accepted for a typed reading.
count is how many cards to get (default 10, at most 30). Items with nothing to answer with are left out: eligible_items says how many could be asked, total_items how many the list has.
The same seed gives the same cards in the same order. Without a seed one is chosen and returned in config.seed: pass it back to get the same cards again.
This does not record any answer or progress and does not check a reply for you.
TEXT)]
#[IsReadOnly]
class GetFlashcardsTool extends Tool
{
    private const DEFAULT_COUNT = 10;

    private const MAX_COUNT = 30;

    private const NOT_FOUND_MESSAGE = 'No public list with that id. Use find_decks to get one.';

    public function __construct(private readonly FlashcardPresenter $presenter)
    {
    }

    public function handle(Request $request, FlashcardDeckServiceInterface $decks): Response|ResponseFactory
    {
        try {
            $input = $request->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            return ToolFailure::fromValidation($exception);
        }

        $prompt = FlashcardField::from($input['prompt'] ?? FlashcardField::CHARACTER->value);
        $answer = FlashcardField::from($input['answer'] ?? FlashcardField::MEANING->value);
        $mode = AnswerMode::from($input['mode'] ?? AnswerMode::TYPED->value);

        // The value objects throw on these two, so the tool answers them first, in the assistant's own words.
        $broken = $this->questionRuleErrors($prompt, $answer, $mode);

        if ($broken !== []) {
            return ToolFailure::fromValidation(ValidationException::withMessages($broken));
        }

        $config = new FlashcardConfig(
            question: new FlashcardQuestion($prompt, $answer, $mode, ScriptStrictness::LENIENT),
            count: (int) ($input['count'] ?? self::DEFAULT_COUNT),
            seed: (int) ($input['seed'] ?? random_int(0, FlashcardConfig::MAX_SEED)),
        );

        $built = $decks->buildDeck(EntityId::from($input['deck']), $config, null);

        if ($built->isFailure()) {
            return $this->failure($built);
        }

        /** @var FlashcardDeckDTO $deck */
        $deck = $built->getData();

        return Response::structured($this->presenter->presentDeck($deck));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $fields = $this->fieldNames();

        return [
            'deck' => $schema->string()
                ->description('The uuid of the list, as find_decks returns it.')
                ->required(),
            'count' => $schema->integer()
                ->description('How many cards to get, 1 to '.self::MAX_COUNT.'.')
                ->min(1)
                ->max(self::MAX_COUNT)
                ->default(self::DEFAULT_COUNT),
            'prompt' => $schema->string()
                ->description('What each card shows: '.implode(', ', $fields).'. Must differ from answer, and one of the two must be character.')
                ->enum($fields)
                ->default(FlashcardField::CHARACTER->value),
            'answer' => $schema->string()
                ->description('What each card asks for: '.implode(', ', $fields).'. A character answer needs mode options.')
                ->enum($fields)
                ->default(FlashcardField::MEANING->value),
            'mode' => $schema->string()
                ->description('typed: the learner types the answer. options: the learner picks one of four.')
                ->enum($this->modeNames())
                ->default(AnswerMode::TYPED->value),
            'seed' => $schema->integer()
                ->description('A whole number from 0 to '.FlashcardConfig::MAX_SEED.'. The same seed gives the same cards in the same order. Leave it out for a new set.')
                ->min(0)
                ->max(FlashcardConfig::MAX_SEED),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'deck' => $schema->object([
                'uuid' => $schema->string()->required(),
                'title' => $schema->string()->required(),
                'kind' => $schema->string()->enum(['kanji', 'words', 'radicals'])->required(),
            ])->required(),
            'config' => $schema->object([
                'prompt' => $schema->string()->enum($this->fieldNames())->required(),
                'answer' => $schema->string()->enum($this->fieldNames())->required(),
                'mode' => $schema->string()->enum($this->modeNames())->required(),
                'count' => $schema->integer()->description('How many cards were asked for.')->required(),
                'seed' => $schema->integer()->description('Pass this as seed to get the same cards again.')->required(),
            ])->required(),
            'cards' => $schema->array()
                ->description('The cards, in the order to ask them. Do not show answer, accepted_answers or options before the learner replies.')
                ->items($schema->object([
                    'prompt' => $schema->string()->description('What to show.')->required(),
                    'hint' => $schema->string()->nullable()->description('A word\'s type, otherwise null.')->required(),
                    'answer' => $schema->string()->description('The one answer to show after the reply.')->required(),
                    'accepted_answers' => $schema->array()->items($schema->string())->description('Every reply that counts as right.')->required(),
                    'options' => $schema->array()->items($schema->string())->nullable()->description('Four choices in options mode, otherwise null.')->required(),
                    'level' => $schema->string()->enum(StudyLevel::names())->nullable()->description('Null when the item has no level.')->required(),
                ]))
                ->required(),
            'total_items' => $schema->integer()->description('How many items the list has.')->required(),
            'eligible_items' => $schema->integer()->description('How many items have something to answer with, before the cut to count.')->required(),
        ];
    }

    /**
     * A list the viewer cannot see answers like one that does not exist, so a private list is not confirmed to exist.
     */
    private function failure(Result $failure): Response
    {
        $status = $failure->getError()->status;

        return $status === HttpStatus::NOT_FOUND || $status === HttpStatus::FORBIDDEN
            ? Response::error(self::NOT_FOUND_MESSAGE)
            : ToolFailure::fromResult($failure);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function questionRuleErrors(FlashcardField $prompt, FlashcardField $answer, AnswerMode $mode): array
    {
        $errors = [];

        if (! FlashcardQuestion::fieldsDiffer($prompt, $answer)) {
            $errors['answer'][] = 'must differ from prompt: a card cannot ask for what it shows.';
        }

        if (! FlashcardQuestion::modeFitsAnswer($mode, $answer)) {
            $errors['mode'][] = 'a character answer cannot be typed. Use mode options, or ask for the meaning or a reading instead.';
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function fieldNames(): array
    {
        return array_column(FlashcardField::cases(), 'value');
    }

    /**
     * @return array<int, string>
     */
    private function modeNames(): array
    {
        return [AnswerMode::TYPED->value, AnswerMode::OPTIONS->value];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'deck' => ['bail', 'required', 'string', 'uuid'],
            'count' => ['nullable', 'integer', 'between:1,'.self::MAX_COUNT],
            'prompt' => ['nullable', 'string', Rule::in($this->fieldNames())],
            'answer' => ['nullable', 'string', Rule::in($this->fieldNames())],
            'mode' => ['nullable', 'string', Rule::in($this->modeNames())],
            'seed' => ['nullable', 'integer', 'between:0,'.FlashcardConfig::MAX_SEED],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $deck = 'must be the uuid of a list, as find_decks returns it.';
        $count = 'must be a whole number from 1 to '.self::MAX_COUNT.'.';
        $field = 'must be one of '.implode(', ', $this->fieldNames()).'.';
        $mode = 'must be typed or options.';
        $seed = 'must be a whole number from 0 to '.FlashcardConfig::MAX_SEED.'.';

        return [
            'deck.required' => $deck,
            'deck.string' => $deck,
            'deck.uuid' => $deck,
            'count.integer' => $count,
            'count.between' => $count,
            'prompt.string' => $field,
            'prompt.in' => $field,
            'answer.string' => $field,
            'answer.in' => $field,
            'mode.string' => $mode,
            'mode.in' => $mode,
            'seed.integer' => $seed,
            'seed.between' => $seed,
        ];
    }
}
