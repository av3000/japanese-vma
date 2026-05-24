<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\Articles\Jobs\ProcessArticleWordsJob;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            'id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'title' => 'article',
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_store_article_dispatches_kanji_and_word_processing_jobs(): void
    {
        $user = $this->createUser();
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

        $response->assertStatus(201);
        $articleUuid = $response->json('uuid');

        Bus::assertDispatched(ProcessArticleKanjisJob::class);
        Bus::assertDispatched(
            ProcessArticleWordsJob::class,
            fn (ProcessArticleWordsJob $job): bool => $this->readJobProperty($job, 'articleUuid') === $articleUuid
                && $this->readJobProperty($job, 'articleText') === $titleJp.$contentJp
        );
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

    private function readJobProperty(object $job, string $property): mixed
    {
        $reflection = new ReflectionClass($job);
        $jobProperty = $reflection->getProperty($property);
        $jobProperty->setAccessible(true);

        return $jobProperty->getValue($job);
    }
}
