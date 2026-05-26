<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Sentences;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SentenceV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_sentences_with_pagination(): void
    {
        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $this->createSentence(id: 1, uuid: $firstUuid, content: '私は学生です。', tatoebaEntry: '1001');
        $this->createSentence(id: 2, uuid: $secondUuid, content: '水を飲みます。', tatoebaEntry: '1002');

        $response = $this->getJson('/api/v1/sentences?per_page=1');

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', 1)
            ->assertJsonPath('items.0.uuid', $firstUuid)
            ->assertJsonPath('items.0.content', '私は学生です。')
            ->assertJsonPath('items.0.tatoeba_entry', '1001')
            ->assertJsonPath('items.0.user_id', null)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true);
    }

    public function test_guest_can_filter_sentences_by_keyword_and_tatoeba_entry(): void
    {
        $this->createSentence(id: 1, content: '私は学生です。', tatoebaEntry: '1001');
        $this->createSentence(id: 2, content: '水を飲みます。', tatoebaEntry: '2002');
        $this->createSentence(id: 3, content: '火を見ます。', tatoebaEntry: '3003');

        $keywordResponse = $this->getJson('/api/v1/sentences?keyword=水');

        $keywordResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', 2)
            ->assertJsonPath('pagination.total', 1);

        $entryResponse = $this->getJson('/api/v1/sentences?tatoeba_entry=3003');

        $entryResponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', 3)
            ->assertJsonPath('items.0.tatoeba_entry', '3003');
    }

    public function test_guest_can_fetch_sentence_detail_by_uuid_with_related_kanjis_and_empty_words(): void
    {
        $sentenceUuid = (string) Str::uuid();
        $kanjiUuid = (string) Str::uuid();

        $this->createSentence(id: 10, uuid: $sentenceUuid, content: '水を飲みます。', tatoebaEntry: '5005');
        $this->createKanji(id: 20, uuid: $kanjiUuid, kanji: '水', meaning: 'water');

        DB::table('japanese_sentence_kanji')->insert([
            'sentence_id' => 10,
            'kanji_id' => 20,
        ]);

        $response = $this->getJson("/api/v1/sentences/{$sentenceUuid}");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', 10)
            ->assertJsonPath('uuid', $sentenceUuid)
            ->assertJsonPath('content', '水を飲みます。')
            ->assertJsonPath('tatoeba_entry', '5005')
            ->assertJsonPath('kanjis.0.uuid', $kanjiUuid)
            ->assertJsonPath('kanjis.0.character', '水')
            ->assertJsonPath('words', []);
    }

    public function test_guest_can_fetch_sentence_detail_by_legacy_numeric_id(): void
    {
        $sentenceUuid = (string) Str::uuid();

        $this->createSentence(id: 77, uuid: $sentenceUuid, content: '火を見ます。', tatoebaEntry: '7777');

        $response = $this->getJson('/api/v1/sentences/77');

        $response->assertOk()
            ->assertJsonPath('id', 77)
            ->assertJsonPath('uuid', $sentenceUuid)
            ->assertJsonPath('content', '火を見ます。')
            ->assertJsonPath('kanjis', [])
            ->assertJsonPath('words', []);
    }

    public function test_unknown_sentence_returns_problem_response(): void
    {
        $missingUuid = (string) Str::uuid();

        $response = $this->getJson("/api/v1/sentences/{$missingUuid}");

        $response->assertNotFound()
            ->assertJsonPath('status', 404)
            ->assertJsonPath('title', "Sentence with identifier '{$missingUuid}' not found.");
    }

    public function test_invalid_sentence_identifier_returns_bad_request_problem_response(): void
    {
        $response = $this->getJson('/api/v1/sentences/not-a-valid-identifier');

        $response->assertBadRequest()
            ->assertJsonPath('status', 400)
            ->assertJsonPath('title', 'Identifier must be a valid UUID or numeric sentence ID.');
    }

    public function test_sentence_list_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/sentences?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    private function createSentence(
        int $id,
        ?string $uuid = null,
        string $content = '私は学生です。',
        ?string $tatoebaEntry = '1001',
        ?int $userId = null,
    ): void {
        DB::table('japanese_tatoeba_sentences')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'user_id' => $userId,
            'tatoeba_entry' => $tatoebaEntry,
            'content' => $content,
        ]);
    }

    private function createKanji(int $id, ?string $uuid = null, string $kanji = '水', string $meaning = 'water'): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => $meaning,
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '水',
            'radical_parts' => $kanji,
        ]);
    }
}
