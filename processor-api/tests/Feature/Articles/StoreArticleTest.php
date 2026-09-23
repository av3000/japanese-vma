<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class StoreArticleTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_store_article_dispatches_one_content_processing_job_and_opens_a_pending_row(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $titleJp = '学校の話';
        $contentJp = '学校で勉強します。日本語の本文です。';

        $response = $this->json('POST', '/api/v1/articles', [
            'title_jp' => $titleJp,
            'title_en' => 'School Story',
            'content_jp' => $contentJp,
            'content_en' => 'I study at school in this article.',
            'source_link' => 'https://example.com/source',
            'publicity' => true,
            'tags' => ['#study'],
        ]);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json): AssertableJson => $json
                ->whereType('uuid', 'string')
                ->where('processing_status.status', ProcessingStatus::PENDING->value)
                ->where('processing_status.type', 'article_content_processing')
                ->etc());
        $articleUuid = $response->json('uuid');

        // The row was opened inside the create transaction, so the very next read already has it.
        $this->json('GET', "/api/v1/articles/{$articleUuid}")
            ->assertOk()
            ->assertJsonPath('processing_status.status', ProcessingStatus::PENDING->value);

        $this->assertDatabaseHas('articles', [
            'uuid' => $articleUuid,
            'user_id' => $user->id,
        ]);

        Bus::assertDispatchedTimes(ProcessArticleContentJob::class, 1);
        Bus::assertDispatched(
            ProcessArticleContentJob::class,
            fn (ProcessArticleContentJob $job): bool => $job->articleUuid === $articleUuid && $job->contentVersion === 1,
        );

        $this->assertDatabaseHas('articles', ['uuid' => $articleUuid, 'content_version' => 1]);
        $this->assertDatabaseHas('processing_states', [
            'entity_type' => 'article',
            'entity_id' => $articleUuid,
            'task_type' => 'article_content_processing',
            'status' => ProcessingStatus::PENDING->value,
            'content_version' => 1,
        ]);
    }
}
