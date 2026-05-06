<?php

namespace Tests\Feature\Legacy;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class HashtagForeignKeyCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedObjectTemplate(ObjectTemplateType::ARTICLE);
        $this->seedObjectTemplate(ObjectTemplateType::POST);
        $this->seedObjectTemplate(ObjectTemplateType::COMMENT);
    }

    public function test_legacy_show_kanji_loads_article_hashtags_from_hashtag_id_column(): void
    {
        $userId = $this->createUser();
        $kanjiId = $this->createKanji();
        $articleId = $this->createArticle($userId);
        $hashtagId = $this->createUniqueHashtag('#kanji-tag');

        DB::table('article_kanji')->insert([
            'article_id' => $articleId,
            'kanji_id' => $kanjiId,
        ]);

        DB::table('hashtag_entity')->insert([
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'entity_id' => $articleId,
            'user_id' => $userId,
            'hashtag_id' => $hashtagId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/kanji/{$kanjiId}");

        $response->assertOk()
            ->assertJsonPath('articles.data.0.id', $articleId)
            ->assertJsonPath('articles.data.0.hashtags.0.content', '#kanji-tag');
    }

    public function test_legacy_post_search_filters_by_hashtag_using_hashtag_id_column(): void
    {
        $userId = $this->createUser();
        $postId = $this->createPost($userId);
        $hashtagId = $this->createUniqueHashtag('#post-tag');

        DB::table('hashtag_entity')->insert([
            'entity_type_id' => ObjectTemplateType::POST->getLegacyId(),
            'entity_id' => $postId,
            'user_id' => $userId,
            'hashtag_id' => $hashtagId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/posts/search', [
            'keyword' => '#post-tag',
        ]);

        $response->assertOk()
            ->assertJsonPath('posts.data.0.id', $postId)
            ->assertJsonPath('posts.data.0.hashtags.0.content', '#post-tag');
    }

    private function seedObjectTemplate(ObjectTemplateType $type): void
    {
        DB::table('objecttemplates')->insert([
            'id' => $type->getLegacyId(),
            'title' => $type->getTitle(),
            'entity_type_uuid' => $type->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Legacy Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKanji(): int
    {
        return DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => '日',
            'onyomi' => 'ニチ',
            'kunyomi' => 'ひ',
            'meaning' => 'sun',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => '72',
            'radical_parts' => '日',
        ]);
    }

    private function createArticle(int $userId): int
    {
        return DB::table('articles')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $userId,
            'status' => 1,
            'publicity' => true,
            'title_en' => 'Legacy article',
            'content_en' => 'Legacy article content',
            'title_jp' => '記事',
            'content_jp' => '記事本文',
            'source_link' => 'https://example.com/article',
            'n1' => '0',
            'n2' => '0',
            'n3' => '0',
            'n4' => '0',
            'n5' => '0',
            'uncommon' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPost(int $userId): int
    {
        return DB::table('posts')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $userId,
            'type' => '1',
            'title' => 'Legacy post',
            'content' => 'Legacy post body',
            'locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUniqueHashtag(string $content): int
    {
        return DB::table('uniquehashtags')->insertGetId([
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
