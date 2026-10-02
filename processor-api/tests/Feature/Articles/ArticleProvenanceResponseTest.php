<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ContentSource;
use Database\Seeders\ContentSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ArticleProvenanceResponseTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private ContentSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->seed(ContentSourceSeeder::class);
        $this->source = ContentSource::query()->sole();
    }

    public function test_a_user_article_reads_as_user_with_no_source(): void
    {
        $article = Article::factory()->create();

        $this->json('GET', "/api/v1/articles/{$article->uuid}")
            ->assertOk()
            ->assertJsonPath('origin', 'user')
            ->assertJsonPath('source', null);
    }

    public function test_an_imported_article_credits_its_source_on_the_detail_page(): void
    {
        $article = Article::factory()->importedFrom($this->source, 'nd-1')->create();

        $this->json('GET', "/api/v1/articles/{$article->uuid}")
            ->assertOk()
            ->assertJsonPath('origin', 'imported')
            ->assertJsonPath('source', [
                'key' => 'nhk-news',
                'name' => 'NHK News',
                'homepage_url' => 'https://news.web.nhk/newsweb',
            ]);
    }

    public function test_the_list_credits_sources_with_one_query_for_all_of_them(): void
    {
        Article::factory()->count(3)->sequence(
            ['external_id' => 'nd-1'],
            ['external_id' => 'nd-2'],
            ['external_id' => 'nd-3'],
        )->importedFrom($this->source)->create();
        Article::factory()->create();

        DB::enableQueryLog();
        $response = $this->json('GET', '/api/v1/articles');
        $sourceQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from "content_sources"'));

        $response->assertOk();
        $items = collect($response->json('items'));
        self::assertSame(3, $items->where('origin', 'imported')->where('source.key', 'nhk-news')->count());
        self::assertSame(1, $items->where('origin', 'user')->whereNull('source')->count());
        self::assertCount(1, $sourceQueries);
    }
}
