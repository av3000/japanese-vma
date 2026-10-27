<?php

declare(strict_types=1);

namespace Tests\Feature\ContentImport;

use App\Domain\Articles\Enums\ArticleOrigin;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ContentSource;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\ContentImporterUserSeeder;
use Database\Seeders\ContentSourceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ContentImportReferenceDataTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_seeding_registers_nhk_news_once_and_keeps_an_operator_kill_switch(): void
    {
        $this->seed(ContentSourceSeeder::class);

        $source = ContentSource::query()->where('key', ContentSourceSeeder::NHK_NEWS)->sole();
        self::assertTrue($source->enabled);
        self::assertSame('NHK News', $source->name);

        $source->update(['enabled' => false]);
        $this->seed(ContentSourceSeeder::class);

        self::assertSame(1, ContentSource::query()->where('key', ContentSourceSeeder::NHK_NEWS)->count());
        self::assertFalse($source->fresh()->enabled);
    }

    public function test_seeding_creates_one_unprivileged_system_user(): void
    {
        $this->seed(ContentImporterUserSeeder::class);
        $this->seed(ContentImporterUserSeeder::class);

        $users = User::query()->where('email', config('content_import.system_user.email'))->get();

        self::assertCount(1, $users);
        self::assertSame('Content Importer', $users->first()->name);
        self::assertFalse($users->first()->hasRole(UserRole::ADMIN->value));
    }

    public function test_the_system_user_cannot_sign_in(): void
    {
        $this->seed(ContentImporterUserSeeder::class);

        foreach (['', 'password', 'secret123', config('content_import.system_user.email')] as $password) {
            $response = $this->json('POST', '/api/v1/login', [
                'email' => config('content_import.system_user.email'),
                'password' => $password,
            ]);

            self::assertNotSame(200, $response->status(), "Signed in with password '{$password}'");
            self::assertNull($response->json('accessToken'));
        }
    }

    public function test_existing_articles_read_as_user_articles(): void
    {
        $article = Article::factory()->create();

        self::assertSame(ArticleOrigin::User, $article->fresh()->origin);
        self::assertNull($article->fresh()->content_source_id);

        $this->json('GET', "/api/v1/articles/{$article->uuid}")->assertOk();
    }

    public function test_an_external_article_can_be_stored_only_once_per_source(): void
    {
        $this->seed(ContentSourceSeeder::class);
        $source = ContentSource::query()->sole();

        Article::factory()->importedFrom($source, 'nd-1')->create();

        $this->expectException(QueryException::class);
        Article::factory()->importedFrom($source, 'nd-1')->create();
    }

    public function test_deleting_a_source_keeps_its_articles(): void
    {
        $this->seed(ContentSourceSeeder::class);
        $source = ContentSource::query()->sole();
        $article = Article::factory()->importedFrom($source, 'nd-2')->create();

        $source->delete();

        $article->refresh();
        self::assertNull($article->content_source_id);
        self::assertSame(ArticleOrigin::Imported, $article->origin);
    }
}
