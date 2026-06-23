<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Words;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WordV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);
    }

    public function test_guest_can_list_words_with_pagination(): void
    {
        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $this->createWord(id: 1, uuid: $firstUuid, word: '学校', furigana: 'がっこう', meanings: ['school', 'academy']);
        $this->createWord(id: 2, uuid: $secondUuid, word: '勉強', furigana: 'べんきょう', meanings: ['study']);

        $response = $this->getJson('/api/v1/words?per_page=1');

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', 1)
            ->assertJsonPath('items.0.uuid', $firstUuid)
            ->assertJsonPath('items.0.word', '学校')
            ->assertJsonPath('items.0.furigana', 'がっこう')
            ->assertJsonPath('items.0.meaning', 'school, academy')
            ->assertJsonPath('items.0.meanings', ['school', 'academy'])
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true);
    }

    public function test_guest_can_filter_words_by_keyword_and_jlpt(): void
    {
        $this->createWord(id: 1, word: '学校', furigana: 'がっこう', jlpt: 'N5', meanings: ['school']);
        $this->createWord(id: 2, word: '水', furigana: 'みず', jlpt: 'N5', meanings: ['water']);
        $this->createWord(id: 3, word: '政治', furigana: 'せいじ', jlpt: 'N2', meanings: ['politics']);

        $keywordResponse = $this->getJson('/api/v1/words?keyword=water');

        $keywordResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', 2)
            ->assertJsonPath('items.0.word', '水')
            ->assertJsonPath('pagination.total', 1);

        $jlptResponse = $this->getJson('/api/v1/words?jlpt=N2');

        $jlptResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', 3)
            ->assertJsonPath('items.0.word', '政治')
            ->assertJsonPath('items.0.jlpt', 'N2');
    }

    public function test_guest_can_fetch_word_detail_by_uuid(): void
    {
        $uuid = (string) Str::uuid();

        $this->createWord(id: 10, uuid: $uuid, word: '学校', furigana: 'がっこう', meanings: ['school', 'academy']);

        $response = $this->getJson("/api/v1/words/{$uuid}");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', 10)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('word', '学校')
            ->assertJsonPath('furigana', 'がっこう')
            ->assertJsonPath('meaning', 'school, academy')
            ->assertJsonPath('meanings', ['school', 'academy'])
            ->assertJsonPath('word_types', ['noun'])
            ->assertJsonPath('writing_elements', ['学校'])
            ->assertJsonPath('reading_elements', ['がっこう'])
            ->assertJsonPath('word_type', 'noun')
            ->assertJsonPath('word_k_ele', '学校')
            ->assertJsonPath('furigana_r_ele', 'がっこう');
    }

    public function test_guest_can_fetch_word_detail_by_exact_surface(): void
    {
        $uuid = (string) Str::uuid();

        $this->createWord(id: 11, uuid: $uuid, word: '勉強', furigana: 'べんきょう', meanings: ['study']);

        $response = $this->getJson('/api/v1/words/'.rawurlencode('勉強'));

        $response->assertOk()
            ->assertJsonPath('id', 11)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('word', '勉強')
            ->assertJsonPath('meaning', 'study');
    }

    public function test_unknown_word_returns_problem_response(): void
    {
        $missingUuid = (string) Str::uuid();

        $response = $this->getJson("/api/v1/words/{$missingUuid}");

        $response->assertNotFound()
            ->assertJsonPath('status', 404)
            ->assertJsonPath('title', "Word with identifier '{$missingUuid}' not found.");
    }

    public function test_legacy_numeric_word_identifier_returns_bad_request_problem_response(): void
    {
        $this->createWord(id: 77, word: '火', furigana: 'ひ', meanings: ['fire']);

        $response = $this->getJson('/api/v1/words/77');

        $response->assertBadRequest()
            ->assertJsonPath('status', 400)
            ->assertJsonPath('title', 'Identifier must be a valid UUID or exact word surface.');
    }

    public function test_word_list_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/words?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_guest_word_list_includes_null_viewer_catalogue_state(): void
    {
        $this->createWord(id: 101, word: '猫', furigana: 'ねこ', meanings: ['cat']);

        $response = $this->getJson('/api/v1/words?per_page=1&include=viewer_catalogue_state');

        $response->assertOk()
            ->assertJsonPath('items.0.id', 101)
            ->assertJsonPath('items.0.viewer_catalogue_state', null);
    }

    public function test_authenticated_word_list_includes_saved_and_known_viewer_catalogue_state(): void
    {
        $user = $this->createUser();

        $this->createWord(id: 101, word: '猫', furigana: 'ねこ', meanings: ['cat']);
        $this->createWord(id: 102, word: '犬', furigana: 'いぬ', meanings: ['dog']);
        $this->createWord(id: 103, word: '水', furigana: 'みず', meanings: ['water']);

        $savedListId = DB::table('customlists')->insertGetId([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'type' => SavedListType::WORDS->value,
            'title' => 'Words To Review',
            'description' => '',
            'publicity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $knownListId = DB::table('customlists')->insertGetId([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'type' => SavedListType::KNOWNWORDS->value,
            'title' => 'Known Words',
            'description' => '',
            'publicity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customlist_object')->insert([
            [
                'list_id' => $savedListId,
                'listtype_id' => (string) SavedListType::WORDS->value,
                'real_object_id' => 101,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'list_id' => $knownListId,
                'listtype_id' => (string) SavedListType::KNOWNWORDS->value,
                'real_object_id' => 102,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->getJson('/api/v1/words?per_page=3&include=viewer_catalogue_state');

        $response->assertOk()
            ->assertJsonPath('items.0.id', 101)
            ->assertJsonPath('items.0.viewer_catalogue_state.is_saved', true)
            ->assertJsonPath('items.0.viewer_catalogue_state.is_known', false)
            ->assertJsonPath('items.1.id', 102)
            ->assertJsonPath('items.1.viewer_catalogue_state.is_saved', false)
            ->assertJsonPath('items.1.viewer_catalogue_state.is_known', true)
            ->assertJsonPath('items.2.id', 103)
            ->assertJsonPath('items.2.viewer_catalogue_state.is_saved', false)
            ->assertJsonPath('items.2.viewer_catalogue_state.is_known', false);
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }

    /**
     * @param array<int, string> $meanings
     */
    private function createWord(
        int $id,
        ?string $uuid = null,
        string $word = '学校',
        string $furigana = 'がっこう',
        string $jlpt = 'N5',
        array $meanings = ['school'],
    ): void {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => $jlpt,
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => $this->sensePayload($meanings),
        ]);
    }

    /**
     * @param array<int, string> $meanings
     */
    private function sensePayload(array $meanings): string
    {
        return json_encode([
            [
                ['gloss', $meanings],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
