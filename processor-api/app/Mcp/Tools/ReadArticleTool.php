<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Articles\Models\Article;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Mcp\Presenters\ArticleDetailPresenter;
use App\Mcp\Support\StudyLevel;
use App\Mcp\Support\ToolFailure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('read_article')]
#[Title('Read an article')]
#[Description(<<<'TEXT'
Read one public Japanese article, with the words it uses, by the uuid find_articles gave. Reading records no view.
The answer has the Japanese and English title and text, main_level and level_mix (as in find_articles), source_link, and words: word, reading, level and meaning for each word the article uses.
Each text is cut at 10,000 characters: content_jp_truncated and content_en_truncated say when that happened. content_en is null when the article has no English text.
words has at most max_words entries (default 50, at most 200). words_total is how many the article has and words_truncated says whether the list was cut.
Word levels may be missing: no dictionary word has a level assigned yet, so level is null for every word.
A private article, an article that is not published yet and an unknown id all give the same error.
TEXT)]
#[IsReadOnly]
class ReadArticleTool extends Tool
{
    private const DEFAULT_MAX_WORDS = 50;

    private const MAX_WORDS = 200;

    private const NOT_FOUND_MESSAGE = 'No public article with that id. Use find_articles to get one.';

    public function __construct(private readonly ArticleDetailPresenter $presenter)
    {
    }

    public function handle(Request $request, ArticleServiceInterface $articles, WordServiceInterface $words): Response|ResponseFactory
    {
        try {
            $input = $request->validate($this->rules(), $this->messages());
        } catch (ValidationException $exception) {
            return ToolFailure::fromValidation($exception);
        }

        $uid = EntityId::from($input['id']);

        $found = $articles->findPublicArticle($uid);

        if ($found->isFailure()) {
            // Whatever the reason, the answer is the same one: it must not tell a hidden article from a missing one.
            return $found->getError()->status->isServerError()
                ? ToolFailure::fromResult($found)
                : Response::error(self::NOT_FOUND_MESSAGE);
        }

        /** @var Article $article */
        $article = $found->getData();

        // Only now: the word list filters by article and does not check that the article is visible.
        $wordsRead = $this->readWords($words, $uid, (int) ($input['max_words'] ?? self::DEFAULT_MAX_WORDS));

        if ($wordsRead instanceof Response) {
            return $wordsRead;
        }

        [$items, $total] = $wordsRead;

        return Response::structured($this->presenter->present($article, $items, $total));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()
                ->description('The uuid of the article, as find_articles returns it.')
                ->required(),
            'max_words' => $schema->integer()
                ->description('The most words to return, 1 to '.self::MAX_WORDS.'. The answer says how many the article has in all.')
                ->min(1)
                ->max(self::MAX_WORDS)
                ->default(self::DEFAULT_MAX_WORDS),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'uuid' => $schema->string()->required(),
            'title_jp' => $schema->string()->required(),
            'title_en' => $schema->string()->nullable()->required(),
            'content_jp' => $schema->string()->description('The Japanese text, cut at 10,000 characters.')->required(),
            'content_en' => $schema->string()->nullable()->description('The English text, cut at 10,000 characters. Null when the article has none.')->required(),
            'content_jp_truncated' => $schema->boolean()->description('Whether the Japanese text was cut.')->required(),
            'content_en_truncated' => $schema->boolean()->description('Whether the English text was cut.')->required(),
            'main_level' => $schema->string()
                ->enum(StudyLevel::names())
                ->nullable()
                ->description('How hard the article is: the easiest level whose kanji, with every easier level, make up at least 90% of its kanji. Null when it has no kanji, or when too many are uncommon to reach 90%.')
                ->required(),
            'level_mix' => $schema->object([
                'N5' => $schema->integer()->required(),
                'N4' => $schema->integer()->required(),
                'N3' => $schema->integer()->required(),
                'N2' => $schema->integer()->required(),
                'N1' => $schema->integer()->required(),
                'uncommon' => $schema->integer()->description('Kanji with no JLPT level.')->required(),
            ])->description('How many kanji the article has at each level.')->required(),
            'source_link' => $schema->string()->required(),
            'words' => $schema->array()
                ->description('The words the article uses, at most max_words of them.')
                ->items($schema->object([
                    'word' => $schema->string()->required(),
                    'reading' => $schema->string()->required(),
                    'level' => $schema->string()->enum(StudyLevel::names())->nullable()->description('Null when the word has no level.')->required(),
                    'meaning' => $schema->string()->description('The first meanings, separated by semicolons.')->required(),
                ]))
                ->required(),
            'words_total' => $schema->integer()->description('How many words the article has in all.')->required(),
            'words_truncated' => $schema->boolean()->description('Whether words was cut at max_words.')->required(),
        ];
    }

    /**
     * Reads at most the words asked for: a page holds 100 words at most, so a larger list is read as
     * two equal pages, which is one extra word for an odd count above 100. Stops early on the last page.
     *
     * @return array{0: array<int, Word>, 1: int}|Response The words and how many the article has in all.
     */
    private function readWords(WordServiceInterface $words, EntityId $articleUid, int $maxWords): array|Response
    {
        $pages = (int) ceil($maxWords / Pagination::MAX_PER_PAGE);
        $perPage = (int) ceil($maxWords / $pages);

        $items = [];
        $total = 0;

        for ($page = 1; $page <= $pages; $page++) {
            $result = $words->find(WordQueryCriteria::forListing(page: $page, perPage: $perPage, articleId: $articleUid));

            if ($result->isFailure()) {
                return ToolFailure::fromResult($result);
            }

            /** @var WordListResultDTO $list */
            $list = $result->getData();

            $total = $list->pagination['total'];
            $items = array_merge($items, $list->items);

            if (! $list->pagination['has_more']) {
                break;
            }
        }

        return [array_slice($items, 0, $maxWords), $total];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'max_words' => ['nullable', 'integer', 'between:1,'.self::MAX_WORDS],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $idFormat = 'must be the uuid of an article, as find_articles returns it.';
        $maxWords = 'must be a whole number from 1 to '.self::MAX_WORDS.'.';

        return [
            'id.required' => $idFormat,
            'id.string' => $idFormat,
            'id.uuid' => $idFormat,
            'max_words.integer' => $maxWords,
            'max_words.between' => $maxWords,
        ];
    }
}
