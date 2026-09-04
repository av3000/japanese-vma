<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class FactorySupportTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_user_factory_creates_the_persistence_user_with_its_default_role(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'uuid' => $user->uuid,
        ]);
        $this->assertTrue($user->hasRole('common'));
    }

    public function test_article_factory_uses_the_public_approved_article_defaults(): void
    {
        $article = Article::factory()->create();

        $this->assertSame(PublicityStatus::PUBLIC, $article->publicity);
        $this->assertSame(ArticleStatus::APPROVED, $article->status);
        $this->assertSame(ObjectTemplateType::ARTICLE->value, $article->entity_type_uuid);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_catalogue_factory_uses_the_article_list_defaults(): void
    {
        $catalogue = Catalogue::factory()->create();

        $this->assertTrue($catalogue->publicity);
        $this->assertSame(SavedListType::ARTICLES, $catalogue->type);
        $this->assertSame(ObjectTemplateType::LIST->value, $catalogue->entity_type_uuid);
        $this->assertDatabaseHas('customlists', ['id' => $catalogue->id]);
    }
}
