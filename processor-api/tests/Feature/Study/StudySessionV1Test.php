<?php

declare(strict_types=1);

namespace Tests\Feature\Study;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\StudyAttempt;
use App\Infrastructure\Persistence\Models\StudySession;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class StudySessionV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    private function catalogue(SavedListType $type = SavedListType::KANJIS, bool $public = true, ?User $owner = null): Catalogue
    {
        return Catalogue::factory()
            ->byUser($owner ?? User::factory()->create())
            ->ofType($type)
            ->create(['publicity' => $public]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Catalogue $catalogue, array $overrides = []): array
    {
        return array_merge([
            'catalogue_uuid' => $catalogue->uuid,
            'prompt' => 'character',
            'answer' => 'meaning',
            'mode' => 'options',
            'script' => 'strict',
            'card_count' => 10,
        ], $overrides);
    }

    private function attempt(array $overrides = []): array
    {
        return array_merge([
            'item_id' => 42,
            'attempt_no' => 1,
            'given_answer' => 'study',
            'expected_answers' => ['study', 'learning'],
            'is_correct' => true,
            'response_ms' => 1800,
        ], $overrides);
    }

    // ------------------------------------------------------------------ create

    public function test_create_requires_authentication(): void
    {
        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue()))->assertStatus(401);
    }

    public function test_create_stores_a_session_for_a_viewable_catalogue(): void
    {
        $user = User::factory()->create();
        $catalogue = $this->catalogue();
        Passport::actingAs($user, ['*'], 'api');

        $response = $this->postJson('/api/v1/study/sessions', $this->payload($catalogue, [
            'answer' => 'kunyomi',
            'mode' => 'typed',
            'script' => 'lenient',
            'card_count' => 7,
        ]));

        $response->assertStatus(201)->assertJsonStructure(['uuid']);

        $this->assertDatabaseHas('study_sessions', [
            'uuid' => $response->json('uuid'),
            'user_id' => $user->id,
            'catalogue_id' => $catalogue->id,
            'catalogue_type' => SavedListType::KANJIS->value,
            'prompt_field' => 'character',
            'answer_field' => 'kunyomi',
            'answer_mode' => 'typed',
            'script_strictness' => 'lenient',
            'card_count' => 7,
            'correct_count' => null,
            'completed_at' => null,
        ]);
    }

    public function test_create_on_a_private_catalogue_of_another_user_is_forbidden(): void
    {
        $catalogue = $this->catalogue(public: false);
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/study/sessions', $this->payload($catalogue))->assertStatus(403);
    }

    public function test_create_on_own_private_catalogue_is_allowed(): void
    {
        $owner = User::factory()->create();
        $catalogue = $this->catalogue(public: false, owner: $owner);
        Passport::actingAs($owner, ['*'], 'api');

        $this->postJson('/api/v1/study/sessions', $this->payload($catalogue))->assertStatus(201);
    }

    public function test_create_rejects_unsupported_types_and_invalid_combinations(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue(SavedListType::SENTENCES)))
            ->assertStatus(422)
            ->assertJsonPath('title', 'Catalogue type not supported for study');

        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue(), ['answer' => 'reading']))
            ->assertStatus(422)
            ->assertJsonPath('title', 'Invalid flashcard configuration');

        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue(), ['prompt' => 'meaning', 'answer' => 'character', 'mode' => 'typed']))
            ->assertStatus(422)
            ->assertJsonPath('title', 'Invalid flashcard configuration');

        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue(), ['answer' => 'character']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answer']);

        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue(), ['card_count' => 101]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card_count']);

        $this->postJson('/api/v1/study/sessions', $this->payload($this->catalogue(), ['catalogue_uuid' => (string) Str::uuid()]))
            ->assertStatus(404);
    }

    // ---------------------------------------------------------------- attempts

    public function test_attempt_is_stored_with_the_item_type_of_the_catalogue(): void
    {
        $user = User::factory()->create();
        $session = StudySession::factory()->byUser($user)->create();
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['is_correct' => false, 'given_answer' => 'fire']))
            ->assertStatus(201);

        $attempt = StudyAttempt::query()->where('session_id', $session->id)->firstOrFail();

        $this->assertSame(42, $attempt->item_id);
        $this->assertSame(ObjectTemplateType::KANJI->value, $attempt->entity_type_uuid);
        $this->assertSame(1, $attempt->attempt_no);
        $this->assertSame('fire', $attempt->given_answer);
        $this->assertSame(['study', 'learning'], $attempt->expected_answers);
        $this->assertFalse($attempt->is_correct);
        $this->assertSame(1800, $attempt->response_ms);
    }

    public function test_attempt_on_a_words_session_uses_the_word_item_type(): void
    {
        $user = User::factory()->create();
        $catalogue = $this->catalogue(SavedListType::KNOWNWORDS, owner: $user);
        $session = StudySession::factory()->forCatalogue($catalogue)->create([
            'answer_field' => 'reading',
            'answer_mode' => 'typed',
        ]);
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt())->assertStatus(201);

        $this->assertDatabaseHas('study_attempts', [
            'session_id' => $session->id,
            'entity_type_uuid' => ObjectTemplateType::WORD->value,
        ]);
    }

    public function test_repeating_an_attempt_is_idempotent(): void
    {
        $user = User::factory()->create();
        $session = StudySession::factory()->byUser($user)->create();
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt())->assertStatus(201);
        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['is_correct' => false]))->assertStatus(200);

        $this->assertSame(1, StudyAttempt::query()->where('session_id', $session->id)->count());
        // The first verdict stands.
        $this->assertTrue(StudyAttempt::query()->where('session_id', $session->id)->firstOrFail()->is_correct);

        // A retry of the same card within the session is a new attempt number.
        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['attempt_no' => 2]))->assertStatus(201);
        $this->assertSame(2, StudyAttempt::query()->where('session_id', $session->id)->count());
    }

    public function test_attempt_on_another_users_session_is_not_found(): void
    {
        $session = StudySession::factory()->create();
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt())
            ->assertStatus(404)
            ->assertJsonPath('title', 'Study session not found');

        $this->assertSame(0, StudyAttempt::query()->count());
    }

    public function test_first_pass_attempt_after_completion_is_a_conflict_but_retry_rounds_are_kept(): void
    {
        $user = User::factory()->create();
        $session = StudySession::factory()->byUser($user)->completed()->create();
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt())
            ->assertStatus(409)
            ->assertJsonPath('title', 'Study session already completed');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['attempt_no' => 2]))
            ->assertStatus(201);

        $this->assertDatabaseHas('study_attempts', ['session_id' => $session->id, 'attempt_no' => 2]);
    }

    public function test_attempt_validation(): void
    {
        $user = User::factory()->create();
        $session = StudySession::factory()->byUser($user)->create();
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['is_correct' => 'yes']))
            ->assertStatus(422)->assertJsonValidationErrors(['is_correct']);
        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['response_ms' => -1]))
            ->assertStatus(422)->assertJsonValidationErrors(['response_ms']);
        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['expected_answers' => 'study']))
            ->assertStatus(422)->assertJsonValidationErrors(['expected_answers']);
        $this->postJson("/api/v1/study/sessions/{$session->uuid}/attempts", $this->attempt(['expected_answers' => [['nested']]]))
            ->assertStatus(422)->assertJsonValidationErrors(['expected_answers.0']);

        $this->postJson('/api/v1/study/sessions/not-a-uuid/attempts', $this->attempt())->assertStatus(404);
    }

    // ---------------------------------------------------------------- complete

    public function test_complete_stores_the_score_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $catalogue = $this->catalogue(owner: $user);
        $session = StudySession::factory()->forCatalogue($catalogue)->create(['card_count' => 10]);
        Passport::actingAs($user, ['*'], 'api');

        $first = $this->postJson("/api/v1/study/sessions/{$session->uuid}/complete", ['correct_count' => 7]);

        $first->assertOk()
            ->assertJsonPath('uuid', $session->uuid)
            ->assertJsonPath('catalogue_uuid', $catalogue->uuid)
            ->assertJsonPath('catalogue_type', SavedListType::KANJIS->value)
            ->assertJsonPath('config.prompt', 'character')
            ->assertJsonPath('config.answer', 'meaning')
            ->assertJsonPath('config.mode', 'options')
            ->assertJsonPath('config.script', 'strict')
            ->assertJsonPath('card_count', 10)
            ->assertJsonPath('correct_count', 7);
        $this->assertNotNull($first->json('completed_at'));

        $this->assertSame(
            ['uuid', 'catalogue_uuid', 'catalogue_type', 'config', 'card_count', 'correct_count', 'started_at', 'completed_at'],
            array_keys($first->json()),
        );

        $second = $this->postJson("/api/v1/study/sessions/{$session->uuid}/complete", ['correct_count' => 3]);

        $second->assertOk()->assertJsonPath('correct_count', 7);
        $this->assertSame($first->json('completed_at'), $second->json('completed_at'));
    }

    public function test_complete_caps_the_score_at_the_card_count(): void
    {
        $user = User::factory()->create();
        $session = StudySession::factory()->byUser($user)->create(['card_count' => 5]);
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/complete", ['correct_count' => 9])
            ->assertOk()
            ->assertJsonPath('correct_count', 5);
    }

    public function test_complete_on_another_users_session_is_not_found(): void
    {
        $session = StudySession::factory()->create();
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson("/api/v1/study/sessions/{$session->uuid}/complete", ['correct_count' => 1])->assertStatus(404);
        $this->postJson('/api/v1/study/sessions/'.Str::uuid().'/complete', ['correct_count' => 1])->assertStatus(404);
    }
}
