# Sentences V1 Backend Read/Detail Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add public v1 sentence list and detail contracts for B7, with clean backend architecture and generated-client-ready OpenAPI.

**Architecture:** Follow the current Radicals v1 module shape: `routes/api_v1.php -> Http/v1 Controller -> Request -> Domain QueryCriteria -> Application Service -> Repository Interface -> Infrastructure Repository/Model/Mapper -> Resource -> TypedResults`. Keep legacy sentence endpoints untouched. Sentence detail includes related kanjis and returns `words: []` as an intentional transitional contract because the sentence-word persistence relation is not available yet.

**Tech Stack:** Laravel 12, PHP 8.3, PHPUnit 11, Scramble/OpenAPI, Orval downstream generation.

---

## Scope

Implement only public read endpoints:

- `GET /api/v1/sentences`
- `GET /api/v1/sentences/{identifier}`

Out of scope:

- sentence create/update/delete
- sentence comments
- sentence likes
- catalogue membership
- frontend migration
- sentence PDF export
- creating a `japanese_sentence_word` relation

Contract decisions:

- List response uses direct v1 shape: `{ items, pagination }`.
- Detail response is direct resource shape, not `{ success, data }`.
- Detail supports UUID and positive numeric legacy IDs.
- Detail includes `kanjis`.
- Detail includes `words: []`.
- Add a concrete code comment at the `words` field explaining that the sentence-word relation is not represented in persistence yet.

---

## File Map

Create:

- `processor-api/app/Domain/JapaneseMaterial/Sentences/Models/Sentence.php`
- `processor-api/app/Domain/JapaneseMaterial/Sentences/DTOs/SentenceListResultDTO.php`
- `processor-api/app/Domain/JapaneseMaterial/Sentences/Queries/SentenceQueryCriteria.php`
- `processor-api/app/Domain/JapaneseMaterial/Sentences/Errors/SentenceErrors.php`
- `processor-api/app/Application/JapaneseMaterial/Sentences/Interfaces/Repositories/SentenceRepositoryInterface.php`
- `processor-api/app/Application/JapaneseMaterial/Sentences/Services/SentenceServiceInterface.php`
- `processor-api/app/Application/JapaneseMaterial/Sentences/Services/SentenceService.php`
- `processor-api/app/Infrastructure/Persistence/Models/Sentence.php`
- `processor-api/app/Infrastructure/Persistence/Repositories/SentenceMapper.php`
- `processor-api/app/Infrastructure/Persistence/Repositories/SentenceRepository.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Requests/IndexSentenceRequest.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Resources/SentenceListResource.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Resources/SentenceResource.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Controllers/SentenceController.php`
- `processor-api/tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php`
- `processor-api/tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php`

Modify:

- `processor-api/routes/api_v1.php`
- `processor-api/app/Providers/RepositoryServiceProvider.php`
- `processor-api/app/Providers/ArticlesServiceProvider.php`
- generated after implementation: `processor-api/api.json`

---

## Task 1: Add Failing Feature Tests

**Files:**

- Create: `processor-api/tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php`

- [ ] **Step 1: Create the feature test file**

Add `processor-api/tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Sentences;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SentenceV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_sentences_with_pagination(): void
    {
        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $this->createSentence(id: 1, uuid: $firstUuid, content: '私は学生です。', tatoebaEntry: '1001');
        $this->createSentence(id: 2, uuid: $secondUuid, content: '水を飲みます。', tatoebaEntry: '1002');

        $response = $this->getJson('/api/v1/sentences?per_page=1');

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', 1)
            ->assertJsonPath('items.0.uuid', $firstUuid)
            ->assertJsonPath('items.0.content', '私は学生です。')
            ->assertJsonPath('items.0.tatoeba_entry', '1001')
            ->assertJsonPath('items.0.user_id', null)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true);
    }

    public function test_guest_can_filter_sentences_by_keyword_and_tatoeba_entry(): void
    {
        $this->createSentence(id: 1, content: '私は学生です。', tatoebaEntry: '1001');
        $this->createSentence(id: 2, content: '水を飲みます。', tatoebaEntry: '2002');
        $this->createSentence(id: 3, content: '火を見ます。', tatoebaEntry: '3003');

        $keywordResponse = $this->getJson('/api/v1/sentences?keyword=水');

        $keywordResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', 2)
            ->assertJsonPath('pagination.total', 1);

        $entryResponse = $this->getJson('/api/v1/sentences?tatoeba_entry=3003');

        $entryResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', 3)
            ->assertJsonPath('items.0.tatoeba_entry', '3003');
    }

    public function test_guest_can_fetch_sentence_detail_by_uuid_with_related_kanjis_and_empty_words(): void
    {
        $sentenceUuid = (string) Str::uuid();
        $kanjiUuid = (string) Str::uuid();

        $this->createSentence(id: 10, uuid: $sentenceUuid, content: '水を飲みます。', tatoebaEntry: '5005');
        $this->createKanji(id: 20, uuid: $kanjiUuid, kanji: '水', meaning: 'water');

        DB::table('japanese_sentence_kanji')->insert([
            'sentence_id' => 10,
            'kanji_id' => 20,
        ]);

        $response = $this->getJson("/api/v1/sentences/{$sentenceUuid}");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', 10)
            ->assertJsonPath('uuid', $sentenceUuid)
            ->assertJsonPath('content', '水を飲みます。')
            ->assertJsonPath('tatoeba_entry', '5005')
            ->assertJsonPath('kanjis.0.uuid', $kanjiUuid)
            ->assertJsonPath('kanjis.0.character', '水')
            ->assertJsonPath('words', []);
    }

    public function test_guest_can_fetch_sentence_detail_by_legacy_numeric_id(): void
    {
        $sentenceUuid = (string) Str::uuid();

        $this->createSentence(id: 77, uuid: $sentenceUuid, content: '火を見ます。', tatoebaEntry: '7777');

        $response = $this->getJson('/api/v1/sentences/77');

        $response->assertOk()
            ->assertJsonPath('id', 77)
            ->assertJsonPath('uuid', $sentenceUuid)
            ->assertJsonPath('content', '火を見ます。')
            ->assertJsonPath('kanjis', [])
            ->assertJsonPath('words', []);
    }

    public function test_unknown_sentence_returns_problem_response(): void
    {
        $missingUuid = (string) Str::uuid();

        $response = $this->getJson("/api/v1/sentences/{$missingUuid}");

        $response->assertNotFound()
            ->assertJsonPath('status', 404)
            ->assertJsonPath('title', "Sentence with identifier '{$missingUuid}' not found.");
    }

    public function test_invalid_sentence_identifier_returns_bad_request_problem_response(): void
    {
        $response = $this->getJson('/api/v1/sentences/not-a-valid-identifier');

        $response->assertBadRequest()
            ->assertJsonPath('status', 400)
            ->assertJsonPath('title', 'Identifier must be a valid UUID or numeric sentence ID.');
    }

    public function test_sentence_list_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/sentences?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    private function createSentence(
        int $id,
        ?string $uuid = null,
        string $content = '私は学生です。',
        ?string $tatoebaEntry = '1001',
        ?int $userId = null,
    ): void {
        DB::table('japanese_tatoeba_sentences')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'user_id' => $userId,
            'tatoeba_entry' => $tatoebaEntry,
            'content' => $content,
        ]);
    }

    private function createKanji(int $id, ?string $uuid = null, string $kanji = '水', string $meaning = 'water'): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => $meaning,
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '水',
            'radical_parts' => $kanji,
        ]);
    }
}
```

- [ ] **Step 2: Run feature tests and confirm they fail**

Run from `processor-api/`:

```powershell
docker compose exec test-runner composer test -- tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php
```

Expected:

- Fails with 404s for missing `/api/v1/sentences` routes.

- [ ] **Step 3: Commit failing tests**

```powershell
git add tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php
git commit -m "test: describe sentence v1 read contracts"
```

---

## Task 2: Add Sentence Domain And Application Contracts

**Files:**

- Create: `processor-api/app/Domain/JapaneseMaterial/Sentences/Models/Sentence.php`
- Create: `processor-api/app/Domain/JapaneseMaterial/Sentences/DTOs/SentenceListResultDTO.php`
- Create: `processor-api/app/Domain/JapaneseMaterial/Sentences/Queries/SentenceQueryCriteria.php`
- Create: `processor-api/app/Domain/JapaneseMaterial/Sentences/Errors/SentenceErrors.php`
- Create: `processor-api/app/Application/JapaneseMaterial/Sentences/Interfaces/Repositories/SentenceRepositoryInterface.php`
- Create: `processor-api/app/Application/JapaneseMaterial/Sentences/Services/SentenceServiceInterface.php`
- Create: `processor-api/app/Application/JapaneseMaterial/Sentences/Services/SentenceService.php`

- [ ] **Step 1: Create domain model**

Add `processor-api/app/Domain/JapaneseMaterial/Sentences/Models/Sentence.php`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Models;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class Sentence
{
    /**
     * @param array<int, DomainKanji> $kanjis
     * @param array<int, mixed> $words
     */
    public function __construct(
        private int $id,
        private EntityId $uuid,
        private ?int $userId,
        private ?string $tatoebaEntry,
        private string $content,
        private array $kanjis = [],
        private array $words = [],
    ) {}

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTatoebaEntry(): ?string
    {
        return $this->tatoebaEntry;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<int, DomainKanji>
     */
    public function getKanjis(): array
    {
        return $this->kanjis;
    }

    /**
     * @return array<int, mixed>
     */
    public function getWords(): array
    {
        return $this->words;
    }
}
```

- [ ] **Step 2: Create list DTO**

Add `processor-api/app/Domain/JapaneseMaterial/Sentences/DTOs/SentenceListResultDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\DTOs;

use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;

final readonly class SentenceListResultDTO
{
    /**
     * @param array<int, Sentence> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {}
}
```

- [ ] **Step 3: Create query criteria**

Add `processor-api/app/Domain/JapaneseMaterial/Sentences/Queries/SentenceQueryCriteria.php`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Queries;

use App\Domain\Shared\ValueObjects\Pagination;

final readonly class SentenceQueryCriteria
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        public Pagination $pagination,
        public ?string $keyword = null,
        public ?string $content = null,
        public ?string $tatoebaEntry = null,
        public ?int $userId = null,
    ) {}

    public static function forListing(
        int $page = Pagination::MIN_PAGE,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $keyword = null,
        ?string $content = null,
        ?string $tatoebaEntry = null,
        ?int $userId = null,
    ): self {
        return new self(
            pagination: new Pagination($page, $perPage),
            keyword: $keyword,
            content: $content,
            tatoebaEntry: $tatoebaEntry,
            userId: $userId,
        );
    }
}
```

- [ ] **Step 4: Create typed errors**

Add `processor-api/app/Domain/JapaneseMaterial/Sentences/Errors/SentenceErrors.php`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

final readonly class SentenceErrors
{
    public static function notFound(string $identifier): ResultError
    {
        return new ResultError(
            'SENTENCE_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Sentence with identifier '{$identifier}' not found.",
        );
    }

    public static function invalidIdentifier(): ResultError
    {
        return new ResultError(
            'INVALID_SENTENCE_IDENTIFIER',
            HttpStatus::BAD_REQUEST,
            'Identifier must be a valid UUID or numeric sentence ID.',
        );
    }
}
```

- [ ] **Step 5: Create repository port**

Add `processor-api/app/Application/JapaneseMaterial/Sentences/Interfaces/Repositories/SentenceRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories;

use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;

interface SentenceRepositoryInterface
{
    public function find(SentenceQueryCriteria $criteria): SentenceListResultDTO;

    public function findByUuid(EntityId $uuid, bool $withKanjis = false): ?Sentence;

    public function findByLegacyId(int $id, bool $withKanjis = false): ?Sentence;
}
```

- [ ] **Step 6: Create service interface and service**

Add `processor-api/app/Application/JapaneseMaterial/Sentences/Services/SentenceServiceInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Shared\Results\Result;

interface SentenceServiceInterface
{
    public function find(SentenceQueryCriteria $criteria): Result;

    public function findByIdentifier(string $identifier, bool $withKanjis = true): Result;
}
```

Add `processor-api/app/Application/JapaneseMaterial/Sentences/Services/SentenceService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\JapaneseMaterial\Sentences\Errors\SentenceErrors;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class SentenceService implements SentenceServiceInterface
{
    public function __construct(
        private readonly SentenceRepositoryInterface $sentenceRepository,
    ) {}

    public function find(SentenceQueryCriteria $criteria): Result
    {
        return Result::success($this->sentenceRepository->find($criteria));
    }

    public function findByIdentifier(string $identifier, bool $withKanjis = true): Result
    {
        if (EntityId::isValid($identifier)) {
            $sentence = $this->sentenceRepository->findByUuid(EntityId::from($identifier), $withKanjis);

            return $sentence
                ? Result::success($sentence)
                : Result::failure(SentenceErrors::notFound($identifier));
        }

        if (ctype_digit($identifier) && (int) $identifier > 0) {
            $sentence = $this->sentenceRepository->findByLegacyId((int) $identifier, $withKanjis);

            return $sentence
                ? Result::success($sentence)
                : Result::failure(SentenceErrors::notFound($identifier));
        }

        return Result::failure(SentenceErrors::invalidIdentifier());
    }
}
```

- [ ] **Step 7: Commit contracts**

```powershell
git add app/Domain/JapaneseMaterial/Sentences app/Application/JapaneseMaterial/Sentences
git commit -m "feat: add sentence read domain contracts"
```

---

## Task 3: Add Persistence Model, Mapper, Repository, And Mapper Tests

**Files:**

- Create: `processor-api/app/Infrastructure/Persistence/Models/Sentence.php`
- Create: `processor-api/app/Infrastructure/Persistence/Repositories/SentenceMapper.php`
- Create: `processor-api/app/Infrastructure/Persistence/Repositories/SentenceRepository.php`
- Create: `processor-api/tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php`

- [ ] **Step 1: Add mapper unit tests**

Add `processor-api/tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Sentences;

use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;
use App\Infrastructure\Persistence\Repositories\KanjiMapper;
use App\Infrastructure\Persistence\Repositories\SentenceMapper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class SentenceMapperTest extends TestCase
{
    public function test_maps_sentence_to_domain_without_relations(): void
    {
        $uuid = (string) Str::uuid();
        $persistenceSentence = new PersistenceSentence([
            'uuid' => $uuid,
            'user_id' => null,
            'tatoeba_entry' => '1001',
            'content' => '私は学生です。',
        ]);
        $persistenceSentence->id = 1;

        $domainSentence = $this->mapper()->mapToDomain($persistenceSentence);

        $this->assertInstanceOf(DomainSentence::class, $domainSentence);
        $this->assertSame(1, $domainSentence->getIdValue());
        $this->assertSame($uuid, $domainSentence->getUuid()->value());
        $this->assertNull($domainSentence->getUserId());
        $this->assertSame('1001', $domainSentence->getTatoebaEntry());
        $this->assertSame('私は学生です。', $domainSentence->getContent());
        $this->assertSame([], $domainSentence->getKanjis());
        $this->assertSame([], $domainSentence->getWords());
    }

    public function test_words_are_empty_until_sentence_word_relation_exists(): void
    {
        $persistenceSentence = new PersistenceSentence([
            'uuid' => (string) Str::uuid(),
            'user_id' => 5,
            'tatoeba_entry' => null,
            'content' => '水を飲みます。',
        ]);
        $persistenceSentence->id = 2;
        $persistenceSentence->setRelation('kanjis', new Collection());

        $domainSentence = $this->mapper()->mapToDomain($persistenceSentence);

        $this->assertSame([], $domainSentence->getWords());
    }

    private function mapper(): SentenceMapper
    {
        return new SentenceMapper(new KanjiMapper());
    }
}
```

- [ ] **Step 2: Run mapper test and confirm it fails**

```powershell
docker compose exec test-runner composer test -- tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php
```

Expected:

- Fails because `Persistence\Models\Sentence` and `SentenceMapper` do not exist.

- [ ] **Step 3: Create persistence model**

Add `processor-api/app/Infrastructure/Persistence/Models/Sentence.php`:

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sentence extends Model
{
    protected $table = 'japanese_tatoeba_sentences';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'tatoeba_entry',
        'content',
    ];

    protected $casts = [
        'uuid' => 'string',
        'user_id' => 'integer',
        'tatoeba_entry' => 'string',
        'content' => 'string',
    ];

    public function kanjis(): BelongsToMany
    {
        return $this->belongsToMany(Kanji::class, 'japanese_sentence_kanji');
    }
}
```

- [ ] **Step 4: Create mapper**

Add `processor-api/app/Infrastructure/Persistence/Repositories/SentenceMapper.php`:

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;

class SentenceMapper
{
    public function __construct(
        private readonly KanjiMapper $kanjiMapper,
    ) {}

    public function mapToDomain(PersistenceSentence $persistenceSentence): DomainSentence
    {
        $kanjis = [];

        if ($persistenceSentence->relationLoaded('kanjis')) {
            $kanjis = $persistenceSentence->kanjis
                ->map(fn (PersistenceKanji $kanji) => $this->kanjiMapper->mapToDomain($kanji))
                ->all();
        }

        return new DomainSentence(
            id: (int) $persistenceSentence->id,
            uuid: new EntityId((string) $persistenceSentence->uuid),
            userId: $persistenceSentence->user_id === null ? null : (int) $persistenceSentence->user_id,
            tatoebaEntry: $persistenceSentence->tatoeba_entry,
            content: (string) $persistenceSentence->content,
            kanjis: $kanjis,
            // Sentence-word relation is not represented in persistence yet; keep the v1 contract stable with an empty array.
            words: [],
        );
    }
}
```

- [ ] **Step 5: Create repository**

Add `processor-api/app/Infrastructure/Persistence/Repositories/SentenceRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;
use Illuminate\Database\Eloquent\Builder;

class SentenceRepository implements SentenceRepositoryInterface
{
    public function __construct(
        private readonly SentenceMapper $sentenceMapper,
    ) {}

    public function find(SentenceQueryCriteria $criteria): SentenceListResultDTO
    {
        $query = PersistenceSentence::query()->orderBy('id');

        $this->applyFilters($query, $criteria);

        $paginator = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        $items = $paginator->getCollection()
            ->map(fn (PersistenceSentence $sentence): DomainSentence => $this->sentenceMapper->mapToDomain($sentence))
            ->all();

        return new SentenceListResultDTO(
            items: $items,
            pagination: [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        );
    }

    public function findByUuid(EntityId $uuid, bool $withKanjis = false): ?DomainSentence
    {
        $query = PersistenceSentence::query()->where('uuid', $uuid->value());

        if ($withKanjis) {
            $query->with('kanjis');
        }

        $sentence = $query->first();

        return $sentence ? $this->sentenceMapper->mapToDomain($sentence) : null;
    }

    public function findByLegacyId(int $id, bool $withKanjis = false): ?DomainSentence
    {
        $query = PersistenceSentence::query()->whereKey($id);

        if ($withKanjis) {
            $query->with('kanjis');
        }

        $sentence = $query->first();

        return $sentence ? $this->sentenceMapper->mapToDomain($sentence) : null;
    }

    private function applyFilters(Builder $query, SentenceQueryCriteria $criteria): void
    {
        if ($criteria->keyword !== null && $criteria->keyword !== '') {
            $keyword = $criteria->keyword;

            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('content', 'LIKE', "%{$keyword}%")
                    ->orWhere('tatoeba_entry', 'LIKE', "%{$keyword}%");
            });
        }

        if ($criteria->content !== null && $criteria->content !== '') {
            $query->where('content', 'LIKE', "%{$criteria->content}%");
        }

        if ($criteria->tatoebaEntry !== null && $criteria->tatoebaEntry !== '') {
            $query->where('tatoeba_entry', 'LIKE', "%{$criteria->tatoebaEntry}%");
        }

        if ($criteria->userId !== null) {
            $query->where('user_id', $criteria->userId);
        }
    }
}
```

- [ ] **Step 6: Run mapper tests**

```powershell
docker compose exec test-runner composer test -- tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php
```

Expected:

- Passes.

- [ ] **Step 7: Commit persistence layer**

```powershell
git add app/Infrastructure/Persistence/Models/Sentence.php app/Infrastructure/Persistence/Repositories/SentenceMapper.php app/Infrastructure/Persistence/Repositories/SentenceRepository.php tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php
git commit -m "feat: add sentence persistence read mapping"
```

---

## Task 4: Add HTTP Request, Resources, Controller, Routes, And Bindings

**Files:**

- Create: `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Requests/IndexSentenceRequest.php`
- Create: `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Resources/SentenceListResource.php`
- Create: `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Resources/SentenceResource.php`
- Create: `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Controllers/SentenceController.php`
- Modify: `processor-api/routes/api_v1.php`
- Modify: `processor-api/app/Providers/RepositoryServiceProvider.php`
- Modify: `processor-api/app/Providers/ArticlesServiceProvider.php`

- [ ] **Step 1: Create request**

Add `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Requests/IndexSentenceRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexSentenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->castIntegerFields([
            'page',
            'per_page',
            'user_id',
        ]));
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'keyword' => ['nullable', 'string', 'min:1', 'max:100'],
            'content' => ['nullable', 'string', 'min:1', 'max:300'],
            'tatoeba_entry' => ['nullable', 'string', 'min:1', 'max:255'],
            'user_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @param array<int, string> $fields
     * @return array<string, int>
     */
    private function castIntegerFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $normalized[$field] = (int) $value;
            }
        }

        return $normalized;
    }
}
```

- [ ] **Step 2: Create resources**

Add `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Resources/SentenceResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Resources;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        DomainSentence $resource,
        private readonly bool $includeKanjis = false,
        private readonly bool $includeWords = false,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     user_id: int|null,
     *     tatoeba_entry: string|null,
     *     content: string,
     *     kanjis?: array<int, KanjiResource>,
     *     words?: array<int, mixed>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var DomainSentence $sentence */
        $sentence = $this->resource;

        $payload = [
            'id' => $sentence->getIdValue(),
            'uuid' => $sentence->getUuid()->value(),
            'user_id' => $sentence->getUserId(),
            'tatoeba_entry' => $sentence->getTatoebaEntry(),
            'content' => $sentence->getContent(),
        ];

        if ($this->includeKanjis) {
            $payload['kanjis'] = array_map(
                fn (DomainKanji $kanji): KanjiResource => new KanjiResource($kanji),
                $sentence->getKanjis(),
            );
        }

        if ($this->includeWords) {
            // Sentence-word relation is not represented in persistence yet; expose an empty array until that relation exists.
            $payload['words'] = $sentence->getWords();
        }

        return $payload;
    }
}
```

Add `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Resources/SentenceListResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Resources;

use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(SentenceListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, SentenceResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var SentenceListResultDTO $result */
        $result = $this->resource;

        return [
            'items' => array_map(
                fn (Sentence $sentence): SentenceResource => new SentenceResource($sentence),
                $result->items,
            ),
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
```

- [ ] **Step 3: Create controller**

Add `processor-api/app/Http/v1/JapaneseMaterial/Sentences/Controllers/SentenceController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Controllers;

use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Controllers\Controller;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\JapaneseMaterial\Sentences\Requests\IndexSentenceRequest;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceListResource;
use App\Http\v1\JapaneseMaterial\Sentences\Resources\SentenceResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SentenceController extends Controller
{
    public function __construct(
        private readonly SentenceServiceInterface $sentenceService,
    ) {}

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

    /**
     * @response array{
     *     id: int,
     *     uuid: string,
     *     user_id: int|null,
     *     tatoeba_entry: string|null,
     *     content: string,
     *     kanjis: array<int, KanjiResource>,
     *     words: array<int, mixed>
     * }
     */
    #[Response(type: 'array{id: int, uuid: string, user_id: int|null, tatoeba_entry: string|null, content: string, kanjis: array<int, KanjiResource>, words: array<int, mixed>}')]
    public function show(string $identifier): JsonResponse|JsonResource
    {
        $result = $this->sentenceService->findByIdentifier($identifier, withKanjis: true);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new SentenceResource($result->getData(), includeKanjis: true, includeWords: true);
    }
}
```

- [ ] **Step 4: Wire routes**

Modify `processor-api/routes/api_v1.php`:

```php
use App\Http\v1\JapaneseMaterial\Sentences\Controllers\SentenceController;
```

Add routes in the public routes section near Kanji/Radicals:

```php
    // Sentences
    Route::get('sentences', [SentenceController::class, 'index']);
    Route::get('sentences/{identifier}', [SentenceController::class, 'show']);
```

- [ ] **Step 5: Wire repository and service bindings**

Modify `processor-api/app/Providers/RepositoryServiceProvider.php` imports:

```php
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\SentenceRepository;
```

Add inside `register()`:

```php
        $this->app->singleton(
            SentenceRepositoryInterface::class,
            SentenceRepository::class
        );
```

Modify `processor-api/app/Providers/ArticlesServiceProvider.php` imports:

```php
use App\Application\JapaneseMaterial\Sentences\Services\SentenceService;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
```

Add inside `register()` near Kanji/Radical service bindings:

```php
        $this->app->bind(SentenceServiceInterface::class, SentenceService::class);
```

- [ ] **Step 6: Run feature tests**

```powershell
docker compose exec test-runner composer test -- tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php
```

Expected:

- All sentence v1 feature tests pass.

- [ ] **Step 7: Commit HTTP layer**

```powershell
git add app/Http/v1/JapaneseMaterial/Sentences routes/api_v1.php app/Providers/RepositoryServiceProvider.php app/Providers/ArticlesServiceProvider.php
git commit -m "feat: add sentence v1 read endpoints"
```

---

## Task 5: Regenerate OpenAPI And Verify Generated Contract

**Files:**

- Modify generated: `processor-api/api.json`

- [ ] **Step 1: Export OpenAPI**

Run from `processor-api/`:

```powershell
docker compose exec laravel-app composer openapi
```

Expected:

- `processor-api/api.json` includes:
  - `/sentences`
  - `/sentences/{identifier}`
  - `SentenceIndexParams` or equivalent query params
  - detail shape with `kanjis` and `words`

- [ ] **Step 2: Inspect generated schema**

Run from repo root:

```powershell
rg -n '"/sentences"|"/sentences/\\{identifier\\}"|Sentence|sentenceIndex|sentenceShow|words' processor-api/api.json
```

Expected:

- Matches show both v1 sentence endpoints.
- Detail schema contains `words`.
- List schema contains `items` and `pagination`.

- [ ] **Step 3: Commit schema**

```powershell
git add processor-api/api.json
git commit -m "chore: export sentence v1 openapi contract"
```

---

## Task 6: Final Backend Verification

**Files:**

- All files changed in Tasks 1-5.

- [ ] **Step 1: Run focused tests**

Run from `processor-api/`:

```powershell
docker compose exec test-runner composer test -- tests/Unit/JapaneseMaterial/Sentences/SentenceMapperTest.php tests/Feature/JapaneseMaterial/Sentences/SentenceV1Test.php
```

Expected:

- Unit and feature tests pass.

- [ ] **Step 2: Run formatting**

Run from `processor-api/`:

```powershell
docker compose exec laravel-app composer format
```

Expected:

- Pint formats touched PHP files.
- If formatting changes files, commit them:

```powershell
git add app tests
git commit -m "style: format sentence v1 backend"
```

- [ ] **Step 3: Run static analysis if practical**

Run from `processor-api/`:

```powershell
docker compose exec laravel-app composer stan
```

Expected:

- Passes, or reports existing repo-wide baseline issues unrelated to B7.
- If sentence-specific issues appear, fix them before continuing.

- [ ] **Step 4: Regenerate OpenAPI after formatting if needed**

Run from `processor-api/` if formatting or schema annotations changed after Task 5:

```powershell
docker compose exec laravel-app composer openapi
```

Then:

```powershell
git diff -- processor-api/api.json
```

Expected:

- No unintended schema drift.

- [ ] **Step 5: Check worktree**

Run from repo root:

```powershell
git status --short
```

Expected:

- Only intentional B7 files are modified or untracked.
- Existing unrelated untracked docs may remain untouched.

---

## Acceptance Criteria Trace

- `/api/v1/sentences` exists and is documented: Task 4 routes/controller and Task 5 OpenAPI.
- `/api/v1/sentences/{identifier}` exists and is documented: Task 4 routes/controller and Task 5 OpenAPI.
- Detail supports related kanjis: Task 3 repository eager loading and Task 4 `SentenceResource`.
- Detail supports approved words behavior: Task 4 returns `words: []` with explicit relation-needed comment.
- No persistence/framework objects leak upward: Task 2 domain/application contracts and Task 3 mapper/repository.
- Backend tests cover list, search/filter, detail, includes, invalid identifier, and not found: Task 1 tests.
- OpenAPI generates usable clients: Task 5 schema export and inspection.

## Notes For Future Frontend Issue F8

- Frontend should prefer generated `sentenceIndex` / `sentenceShow` Orval clients after `npm run orval:file`.
- Existing `/sentence/:sentence_id` route can keep numeric IDs initially because B7 supports legacy numeric identifiers.
- Comment migration remains blocked by B8; F8 should avoid expanding `CommentsBlock` behavior except to keep the page stable.
