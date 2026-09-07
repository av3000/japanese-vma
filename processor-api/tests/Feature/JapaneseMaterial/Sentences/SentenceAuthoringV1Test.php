<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Sentences;

use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceWriteDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence as DomainSentence;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Repositories\SentenceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SentenceAuthoringV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);
    }

    public function test_guest_cannot_create_update_or_delete_sentences(): void
    {
        $uuid = (string) Str::uuid();

        $this->postJson('/api/v1/sentences', ['content' => '私は学生です。'])->assertUnauthorized();
        $this->putJson("/api/v1/sentences/{$uuid}", ['content' => '私は先生です。'])->assertUnauthorized();
        $this->deleteJson("/api/v1/sentences/{$uuid}")->assertUnauthorized();
    }

    public function test_create_rejects_content_outside_the_allowed_length(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        foreach ([[], ['content' => '日本語'], ['content' => str_repeat('日', 301)]] as $payload) {
            $this->postJson('/api/v1/sentences', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['content']);
        }
    }

    public function test_update_rejects_content_outside_the_allowed_length(): void
    {
        $owner = $this->createUser();
        $sentence = $this->createSentence($owner->id);
        Passport::actingAs($owner, ['*'], 'api');

        foreach ([[], ['content' => '日本語'], ['content' => str_repeat('日', 301)]] as $payload) {
            $this->putJson("/api/v1/sentences/{$sentence->uuid}", $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['content']);
        }
    }

    public function test_owner_creates_sentence_with_uuid_and_derived_relationships(): void
    {
        $owner = $this->createUser();
        Passport::actingAs($owner, ['*'], 'api');
        $this->createKanji(10, '学');
        $this->createKanji(11, '校');
        $this->createWord(20, '学校', 'がっこう');

        $response = $this->postJson('/api/v1/sentences', ['content' => '学校へ行きます。']);

        $response->assertCreated()
            ->assertJsonPath('user_id', $owner->id)
            ->assertJsonPath('tatoeba_entry', null)
            ->assertJsonPath('content', '学校へ行きます。')
            ->assertJsonFragment(['character' => '学'])
            ->assertJsonFragment(['character' => '校'])
            ->assertJsonFragment(['word' => '学校']);

        $sentenceId = (int) $response->json('id');
        $this->assertTrue(Str::isUuid((string) $response->json('uuid')));
        $this->assertDatabaseHas('japanese_sentence_kanji', ['sentence_id' => $sentenceId, 'kanji_id' => 10]);
        $this->assertDatabaseHas('japanese_sentence_kanji', ['sentence_id' => $sentenceId, 'kanji_id' => 11]);
        $this->assertDatabaseHas('japanese_sentence_word', ['sentence_id' => $sentenceId, 'word_id' => 20]);
    }

    public function test_owner_update_replaces_content_kanjis_and_words(): void
    {
        $owner = $this->createUser();
        $sentence = $this->createSentence($owner->id, '学校へ行きます。');
        Passport::actingAs($owner, ['*'], 'api');
        $this->createKanji(10, '学');
        $this->createKanji(11, '水');
        $this->createWord(20, '学校', 'がっこう');
        $this->createWord(21, '水', 'みず');
        DB::table('japanese_sentence_kanji')->insert(['sentence_id' => $sentence->id, 'kanji_id' => 10]);
        DB::table('japanese_sentence_word')->insert(['sentence_id' => $sentence->id, 'word_id' => 20]);

        $response = $this->putJson("/api/v1/sentences/{$sentence->uuid}", [
            'content' => '水を飲みます。',
        ]);

        $response->assertOk()
            ->assertJsonPath('content', '水を飲みます。')
            ->assertJsonFragment(['character' => '水'])
            ->assertJsonFragment(['word' => '水']);
        $this->assertDatabaseMissing('japanese_sentence_kanji', ['sentence_id' => $sentence->id, 'kanji_id' => 10]);
        $this->assertDatabaseMissing('japanese_sentence_word', ['sentence_id' => $sentence->id, 'word_id' => 20]);
        $this->assertDatabaseHas('japanese_sentence_kanji', ['sentence_id' => $sentence->id, 'kanji_id' => 11]);
        $this->assertDatabaseHas('japanese_sentence_word', ['sentence_id' => $sentence->id, 'word_id' => 21]);
    }

    public function test_non_owner_cannot_update_or_delete_user_authored_sentence(): void
    {
        $sentence = $this->createSentence($this->createUser()->id);
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->putJson("/api/v1/sentences/{$sentence->uuid}", ['content' => '変更された文です。'])
            ->assertForbidden()
            ->assertJsonPath('title', "You do not have permission to modify sentence '{$sentence->uuid}'.");
        $this->deleteJson("/api/v1/sentences/{$sentence->uuid}")
            ->assertForbidden()
            ->assertJsonPath('title', "You do not have permission to modify sentence '{$sentence->uuid}'.");
        $this->assertDatabaseHas('japanese_tatoeba_sentences', ['id' => $sentence->id]);
    }

    public function test_admin_can_update_and_delete_user_authored_sentences(): void
    {
        $sentenceToUpdate = $this->createSentence($this->createUser()->id);
        $sentenceToDelete = $this->createSentence($this->createUser()->id);
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin, ['*'], 'api');

        $this->putJson("/api/v1/sentences/{$sentenceToUpdate->uuid}", ['content' => '管理者が変更しました。'])
            ->assertOk();
        $this->deleteJson("/api/v1/sentences/{$sentenceToDelete->uuid}")
            ->assertNoContent();
    }

    public function test_imported_sentence_is_immutable_even_for_admin(): void
    {
        $sentence = $this->createSentence(null, tatoebaEntry: '1001');
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin, ['*'], 'api');

        $this->putJson("/api/v1/sentences/{$sentence->uuid}", ['content' => '管理者の変更です。'])
            ->assertForbidden()
            ->assertJsonPath('title', "Sentence '{$sentence->uuid}' was imported and cannot be modified.");
        $this->deleteJson("/api/v1/sentences/{$sentence->uuid}")
            ->assertForbidden()
            ->assertJsonPath('title', "Sentence '{$sentence->uuid}' was imported and cannot be modified.");
        $this->assertDatabaseHas('japanese_tatoeba_sentences', ['id' => $sentence->id]);
    }

    public function test_update_and_delete_validate_uuid_and_missing_sentence(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');
        $missingUuid = (string) Str::uuid();

        $this->putJson('/api/v1/sentences/not-a-uuid', ['content' => '有効な長さの文です。'])
            ->assertBadRequest();
        $this->deleteJson('/api/v1/sentences/not-a-uuid')
            ->assertBadRequest();
        $this->putJson("/api/v1/sentences/{$missingUuid}", ['content' => '有効な長さの文です。'])
            ->assertNotFound();
        $this->deleteJson("/api/v1/sentences/{$missingUuid}")
            ->assertNotFound();
    }

    public function test_owner_delete_removes_only_sentence_dependencies(): void
    {
        $owner = $this->createUser();
        $sentence = $this->createSentence($owner->id);
        Passport::actingAs($owner, ['*'], 'api');
        $this->createKanji(10, '学');
        $this->createWord(20, '学校', 'がっこう');
        DB::table('japanese_sentence_kanji')->insert(['sentence_id' => $sentence->id, 'kanji_id' => 10]);
        DB::table('japanese_sentence_word')->insert(['sentence_id' => $sentence->id, 'word_id' => 20]);
        DB::table('customlist_object')->insert([
            ['list_id' => 1, 'listtype_id' => SavedListType::SENTENCES->value, 'real_object_id' => $sentence->id],
            ['list_id' => 2, 'listtype_id' => SavedListType::KNOWNSENTENCES->value, 'real_object_id' => $sentence->id],
            ['list_id' => 3, 'listtype_id' => SavedListType::ARTICLES->value, 'real_object_id' => $sentence->id],
        ]);
        DB::table('views')->insert([
            ['template_id' => ObjectTemplateType::SENTENCE->getLegacyId(), 'real_object_id' => $sentence->id, 'user_id' => $owner->id],
            ['template_id' => ObjectTemplateType::ARTICLE->getLegacyId(), 'real_object_id' => $sentence->id, 'user_id' => $owner->id],
        ]);
        DB::table('downloads')->insert([
            ['template_id' => ObjectTemplateType::SENTENCE->getLegacyId(), 'real_object_id' => $sentence->id, 'user_id' => $owner->id],
            ['template_id' => ObjectTemplateType::ARTICLE->getLegacyId(), 'real_object_id' => $sentence->id, 'user_id' => $owner->id],
        ]);
        DB::table('likes')->insert([
            ['template_id' => ObjectTemplateType::SENTENCE->getLegacyId(), 'real_object_id' => $sentence->id, 'user_id' => $owner->id, 'value' => true],
            ['template_id' => ObjectTemplateType::ARTICLE->getLegacyId(), 'real_object_id' => $sentence->id, 'user_id' => $owner->id, 'value' => true],
        ]);
        $sentenceCommentId = DB::table('comments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::SENTENCE->getLegacyId(),
            'real_object_id' => $sentence->id,
            'user_id' => $owner->id,
            'content' => 'Sentence comment',
        ]);
        $articleCommentId = DB::table('comments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $sentence->id,
            'user_id' => $owner->id,
            'content' => 'Article comment',
        ]);
        DB::table('likes')->insert([
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $sentenceCommentId,
            'user_id' => $owner->id,
            'value' => true,
        ]);

        $this->deleteJson("/api/v1/sentences/{$sentence->uuid}")->assertNoContent();

        $this->assertDatabaseMissing('japanese_tatoeba_sentences', ['id' => $sentence->id]);
        $this->assertDatabaseMissing('japanese_sentence_kanji', ['sentence_id' => $sentence->id]);
        $this->assertDatabaseMissing('japanese_sentence_word', ['sentence_id' => $sentence->id]);
        $this->assertDatabaseMissing('customlist_object', ['real_object_id' => $sentence->id, 'listtype_id' => SavedListType::SENTENCES->value]);
        $this->assertDatabaseMissing('customlist_object', ['real_object_id' => $sentence->id, 'listtype_id' => SavedListType::KNOWNSENTENCES->value]);
        $this->assertDatabaseHas('customlist_object', ['real_object_id' => $sentence->id, 'listtype_id' => SavedListType::ARTICLES->value]);
        foreach (['views', 'downloads', 'likes'] as $table) {
            $this->assertDatabaseMissing($table, ['real_object_id' => $sentence->id, 'template_id' => ObjectTemplateType::SENTENCE->getLegacyId()]);
            $this->assertDatabaseHas($table, ['real_object_id' => $sentence->id, 'template_id' => ObjectTemplateType::ARTICLE->getLegacyId()]);
        }
        $this->assertDatabaseMissing('comments', ['id' => $sentenceCommentId]);
        $this->assertDatabaseHas('comments', ['id' => $articleCommentId]);
        $this->assertDatabaseMissing('likes', ['real_object_id' => $sentenceCommentId, 'template_id' => ObjectTemplateType::COMMENT->getLegacyId()]);
    }

    public function test_create_rolls_back_when_word_synchronization_fails(): void
    {
        $owner = $this->createUser();
        Passport::actingAs($owner, ['*'], 'api');
        $this->bindFailingWordSyncRepository();

        $this->postJson('/api/v1/sentences', ['content' => '私は学生です。'])
            ->assertInternalServerError();

        $this->assertDatabaseMissing('japanese_tatoeba_sentences', ['user_id' => $owner->id]);
        $this->assertDatabaseCount('japanese_sentence_kanji', 0);
        $this->assertDatabaseCount('japanese_sentence_word', 0);
    }

    public function test_update_rolls_back_when_word_synchronization_fails(): void
    {
        $owner = $this->createUser();
        $sentence = $this->createSentence($owner->id, '元の文章です。');
        Passport::actingAs($owner, ['*'], 'api');
        $this->createKanji(10, '元');
        $this->createWord(20, '元', 'もと');
        DB::table('japanese_sentence_kanji')->insert(['sentence_id' => $sentence->id, 'kanji_id' => 10]);
        DB::table('japanese_sentence_word')->insert(['sentence_id' => $sentence->id, 'word_id' => 20]);
        $this->bindFailingWordSyncRepository();

        $this->putJson("/api/v1/sentences/{$sentence->uuid}", ['content' => '変更後の文章です。'])
            ->assertInternalServerError();

        $this->assertDatabaseHas('japanese_tatoeba_sentences', ['id' => $sentence->id, 'content' => '元の文章です。']);
        $this->assertDatabaseHas('japanese_sentence_kanji', ['sentence_id' => $sentence->id, 'kanji_id' => 10]);
        $this->assertDatabaseHas('japanese_sentence_word', ['sentence_id' => $sentence->id, 'word_id' => 20]);
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Sentence Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }

    private function createSentence(
        ?int $userId,
        string $content = '私は学生です。',
        ?string $tatoebaEntry = null,
    ): Sentence {
        return Sentence::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'tatoeba_entry' => $tatoebaEntry,
            'content' => $content,
        ]);
    }

    private function createKanji(int $id, string $character): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'kanji' => $character,
            'onyomi' => 'オン',
            'kunyomi' => 'くん',
            'meaning' => 'meaning',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => $character,
            'radical_parts' => $character,
        ]);
    }

    private function createWord(int $id, string $word, string $furigana): void
    {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => json_encode([[['gloss', ['meaning']]]], JSON_THROW_ON_ERROR),
        ]);
    }

    private function bindFailingWordSyncRepository(): void
    {
        $inner = app(SentenceRepository::class);

        $this->app->instance(SentenceRepositoryInterface::class, new class($inner) implements SentenceRepositoryInterface
        {
            public function __construct(private readonly SentenceRepositoryInterface $inner)
            {
            }

            public function find(SentenceQueryCriteria $criteria): SentenceListResultDTO
            {
                return $this->inner->find($criteria);
            }

            public function findByUuid(EntityId $uuid, bool $withKanjis = false, bool $withWords = false): ?DomainSentence
            {
                return $this->inner->findByUuid($uuid, $withKanjis, $withWords);
            }

            public function findByLegacyId(int $id, bool $withKanjis = false, bool $withWords = false): ?DomainSentence
            {
                return $this->inner->findByLegacyId($id, $withKanjis, $withWords);
            }

            public function create(SentenceWriteDTO $dto, UserId $ownerId, EntityId $uuid): DomainSentence
            {
                return $this->inner->create($dto, $ownerId, $uuid);
            }

            public function updateContent(int $sentenceId, SentenceWriteDTO $dto): void
            {
                $this->inner->updateContent($sentenceId, $dto);
            }

            public function syncKanjis(int $sentenceId, array $kanjiIds): void
            {
                $this->inner->syncKanjis($sentenceId, $kanjiIds);
            }

            public function syncWords(int $sentenceId, array $wordIds): void
            {
                throw new RuntimeException('Forced word synchronization failure.');
            }

            public function delete(int $sentenceId): void
            {
                $this->inner->delete($sentenceId);
            }
        });
    }
}
