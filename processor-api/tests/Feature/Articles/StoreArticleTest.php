<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\Articles\Jobs\ProcessArticleWordsJob;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use ReflectionClass;
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

    public function test_store_article_dispatches_kanji_and_word_processing_jobs(): void
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
                ->etc());
        $articleUuid = $response->json('uuid');

        $this->assertDatabaseHas('articles', [
            'uuid' => $articleUuid,
            'user_id' => $user->id,
        ]);

        Bus::assertDispatched(ProcessArticleKanjisJob::class);
        Bus::assertDispatched(
            ProcessArticleWordsJob::class,
            fn (ProcessArticleWordsJob $job): bool => $this->readJobProperty($job, 'articleUuid') === $articleUuid
                && $this->readJobProperty($job, 'articleText') === $titleJp.$contentJp
        );
    }

    private function readJobProperty(object $job, string $property): mixed
    {
        $reflection = new ReflectionClass($job);
        $jobProperty = $reflection->getProperty($property);
        $jobProperty->setAccessible(true);

        return $jobProperty->getValue($job);
    }
}
