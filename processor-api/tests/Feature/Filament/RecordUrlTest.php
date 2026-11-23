<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Catalogues\CatalogueResource;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Posts\PostResource;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

/**
 * Record pages resolve by uuid, so the URLs Filament builds from a model (table rows, relation
 * managers) must carry the uuid too. A numeric id there used to reach PostgreSQL as an invalid uuid.
 */
class RecordUrlTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    public function test_view_urls_built_from_models_use_the_uuid_and_open(): void
    {
        $this->seedAdminPanel();
        $this->actingAsPanelUser($this->createAdmin());
        $author = $this->createPanelUser('Author');

        $article = Article::factory()->create(['user_id' => $author->id]);
        $post = Post::factory()->create(['user_id' => $author->id]);
        $catalogue = Catalogue::factory()->create(['user_id' => $author->id]);
        $comment = Comment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $author->id,
            'content' => 'Hello',
        ]);

        foreach ([
            [ArticleResource::class, $article],
            [PostResource::class, $post],
            [CatalogueResource::class, $catalogue],
            [CommentResource::class, $comment],
        ] as [$resource, $record]) {
            $url = $resource::getUrl('view', ['record' => $record]);

            self::assertStringEndsWith('/'.$record->uuid, $url);
            $this->get($url)->assertOk();
        }
    }
}
