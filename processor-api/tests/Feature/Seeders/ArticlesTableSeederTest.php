<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article;
use Database\Seeders\ArticlesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ArticlesTableSeederTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_it_seeds_twelve_public_approved_articles_with_requested_localized_fields(): void
    {
        $this->seed(ArticlesTableSeeder::class);

        $articles = Article::query()->orderBy('id')->get();

        self::assertCount(12, $articles);

        foreach ($articles as $article) {
            self::assertMatchesRegularExpression('/[\p{Han}\p{Hiragana}\p{Katakana}]/u', $article->title_jp);
            self::assertMatchesRegularExpression('/[\p{Han}\p{Hiragana}\p{Katakana}]/u', $article->content_jp);
            self::assertMatchesRegularExpression('/[A-Za-z]/', $article->title_en);
            self::assertNotEmpty($article->source_link);
            self::assertNotEmpty($article->uuid);
            self::assertSame(ObjectTemplateType::ARTICLE->value, $article->entity_type_uuid);
            self::assertSame(PublicityStatus::PUBLIC, $article->publicity);
            self::assertSame(ArticleStatus::APPROVED, $article->status);
        }
    }
}
