<?php

declare(strict_types=1);

namespace Tests\Feature\ContentImport;

use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\Enums\SourceTagKind;
use App\Infrastructure\Persistence\Models\SourceTagMapping;
use Database\Seeders\ContentSourceSeeder;
use Database\Seeders\NhkTagMappingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MappingArticleTaggerTest extends TestCase
{
    use RefreshDatabase;

    private ContentSourceDTO $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ContentSourceSeeder::class, NhkTagMappingSeeder::class]);
        $this->source = app(ContentSourceRepositoryInterface::class)->findByKey(ContentSourceSeeder::NHK_NEWS);
    }

    public function test_topics_come_before_genres(): void
    {
        $tags = $this->tag(genres: ['国際'], topics: ['北朝鮮情勢', '韓国']);

        self::assertSame(['#北朝鮮', '#韓国', '#国際'], $tags);
    }

    public function test_it_caps_at_three_tags(): void
    {
        $tags = $this->tag(genres: ['経済', '国際'], topics: ['物価高', '円相場', '株価', '日銀']);

        self::assertSame(['#物価', '#為替', '#株価'], $tags);
    }

    public function test_two_labels_mapping_to_the_same_hashtag_count_once(): void
    {
        $tags = $this->tag(genres: ['国際'], topics: ['中東情勢', 'イスラエル']);

        self::assertSame(['#中東', '#国際'], $tags);
    }

    public function test_unmapped_topics_are_dropped_and_logged_once(): void
    {
        Log::spy();
        $tagger = app(ArticleTaggerInterface::class);

        $first = $tagger->tagsFor($this->source, $this->article(genres: ['社会'], topics: ['クローズアップ現代', '埼玉県']));
        $tagger->tagsFor($this->source, $this->article(genres: ['社会'], topics: ['クローズアップ現代']));

        self::assertSame(['#社会'], $first);
        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $context['topic_name'] === 'クローズアップ現代')
            ->once();
    }

    public function test_an_article_with_nothing_mapped_gets_no_tags(): void
    {
        self::assertSame([], $this->tag(genres: ['地域'], topics: []));
    }

    public function test_reseeding_keeps_an_operators_edit(): void
    {
        SourceTagMapping::query()
            ->where('kind', SourceTagKind::Topic->value)
            ->where('external_key', '生成AI・人工知能')
            ->update(['hashtag' => '#人工知能']);

        $this->seed(NhkTagMappingSeeder::class);

        self::assertSame(['#人工知能'], $this->tag(genres: [], topics: ['生成AI・人工知能']));
    }

    public function test_every_seeded_hashtag_fits_the_tag_column_and_starts_with_a_hash(): void
    {
        foreach (SourceTagMapping::query()->pluck('hashtag') as $hashtag) {
            self::assertStringStartsWith('#', $hashtag);
            self::assertLessThanOrEqual(50, mb_strlen($hashtag));
        }
    }

    /**
     * @param list<string> $genres
     * @param list<string> $topics
     *
     * @return list<string>
     */
    private function tag(array $genres, array $topics): array
    {
        return app(ArticleTaggerInterface::class)->tagsFor($this->source, $this->article($genres, $topics));
    }

    /**
     * @param list<string> $genres
     * @param list<string> $topics
     */
    private function article(array $genres, array $topics): ExternalArticle
    {
        return new ExternalArticle('nd-1', '題名', '本文', 'https://news.web.nhk/newsweb/na/nd-1', null, $genres, array_combine($topics, $topics));
    }
}
