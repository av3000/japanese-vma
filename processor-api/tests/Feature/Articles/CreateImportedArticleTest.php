<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Application\Articles\Services\ArticleServiceInterface;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\DTOs\ArticleCreateResultDTO;
use App\Domain\Articles\ValueObjects\ArticleAuthor;
use App\Domain\Articles\ValueObjects\ArticleProvenance;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Infrastructure\Persistence\Models\ContentSource;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\ContentSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class CreateImportedArticleTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private ArticleAuthor $author;

    private ContentSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->seed(ContentSourceSeeder::class);
        Bus::fake();

        $user = User::factory()->create();
        $this->author = new ArticleAuthor(new UserId($user->id), new UserName($user->name), new EntityId($user->uuid));
        $this->source = ContentSource::query()->sole();
    }

    public function test_an_imported_article_is_created_through_the_same_path_as_a_user_article(): void
    {
        $result = app(ArticleServiceInterface::class)->createArticle($this->dto('nd-1'), $this->author);

        self::assertTrue($result->isSuccess());
        /** @var ArticleCreateResultDTO $created */
        $created = $result->getData();
        self::assertTrue($created->article->getProvenance()->isImported());

        $this->assertDatabaseHas('articles', [
            'uuid' => $created->article->getUid()->value(),
            'origin' => 'imported',
            'content_source_id' => $this->source->id,
            'external_id' => 'nd-1',
            'user_id' => $this->author->id->value(),
        ]);
        $this->assertDatabaseHas('processing_states', [
            'entity_id' => $created->article->getUid()->value(),
            'status' => 'pending',
        ]);
        Bus::assertDispatchedTimes(ProcessArticleContentJob::class, 1);
    }

    public function test_importing_the_same_external_article_twice_is_a_distinct_failure(): void
    {
        $service = app(ArticleServiceInterface::class);
        $service->createArticle($this->dto('nd-2'), $this->author);

        $result = $service->createArticle($this->dto('nd-2'), $this->author);

        self::assertTrue($result->isFailure());
        self::assertSame('Articles.AlreadyImported', $result->getError()->code);
        $this->assertDatabaseCount('articles', 1);
        Bus::assertDispatchedTimes(ProcessArticleContentJob::class, 1);
    }

    public function test_a_dto_without_provenance_creates_a_user_article(): void
    {
        $dto = new ArticleCreateDTO('題名', null, 'これは日本語の本文です。十分な長さです。', null, 'https://example.com/a', true);

        $result = app(ArticleServiceInterface::class)->createArticle($dto, $this->author);

        self::assertTrue($result->isSuccess());
        $this->assertDatabaseHas('articles', ['origin' => 'user', 'content_source_id' => null, 'external_id' => null]);
    }

    private function dto(string $externalId): ArticleCreateDTO
    {
        return new ArticleCreateDTO(
            title_jp: '台風の予測にAI',
            title_en: null,
            content_jp: '次々と襲来する台風。その進路予想に「AI」が革命を起こそうとしています。',
            content_en: null,
            source_link: "https://news.web.nhk/newsweb/na/{$externalId}",
            publicity: true,
            tags: ['気象'],
            provenance: ArticleProvenance::imported($this->source->id, $externalId),
        );
    }
}
