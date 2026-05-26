<?php

declare(strict_types=1);

namespace Tests\Unit\JapaneseMaterial\Sentences;

use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;
use App\Infrastructure\Persistence\Repositories\KanjiMapper;
use App\Infrastructure\Persistence\Repositories\SentenceMapper;
use Illuminate\Support\Str;
use Tests\TestCase;

class SentenceMapperTest extends TestCase
{
    public function test_mapper_maps_sentence_scalar_fields(): void
    {
        $uuid = (string) Str::uuid();
        $persistenceSentence = new PersistenceSentence([
            'user_id' => null,
            'tatoeba_entry' => '1001',
            'content' => '私は学生です。',
        ]);
        $persistenceSentence->id = 85;
        $persistenceSentence->uuid = $uuid;

        $mapper = new SentenceMapper(new KanjiMapper());

        $domainSentence = $mapper->mapToDomain($persistenceSentence);

        $this->assertSame(85, $domainSentence->getIdValue());
        $this->assertSame($uuid, $domainSentence->getUuid()->value());
        $this->assertNull($domainSentence->getUserId());
        $this->assertSame('1001', $domainSentence->getTatoebaEntry());
        $this->assertSame('私は学生です。', $domainSentence->getContent());
        $this->assertSame([], $domainSentence->getKanjis());
        $this->assertSame([], $domainSentence->getWords());
    }

    public function test_mapper_maps_loaded_related_kanjis_and_keeps_words_empty(): void
    {
        $persistenceSentence = new PersistenceSentence([
            'user_id' => 12,
            'tatoeba_entry' => '5005',
            'content' => '水を飲みます。',
        ]);
        $persistenceSentence->id = 1;
        $persistenceSentence->uuid = (string) Str::uuid();

        $persistenceKanji = new PersistenceKanji([
            'kanji' => '水',
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'water',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '水',
            'radical_parts' => '水',
        ]);
        $persistenceKanji->id = 10;
        $persistenceKanji->uuid = (string) Str::uuid();

        $persistenceSentence->setRelation('kanjis', collect([$persistenceKanji]));

        $mapper = new SentenceMapper(new KanjiMapper());

        $domainSentence = $mapper->mapToDomain($persistenceSentence);

        $this->assertSame(12, $domainSentence->getUserId());
        $this->assertCount(1, $domainSentence->getKanjis());
        $this->assertSame('水', $domainSentence->getKanjis()[0]->getCharacter()->value());
        $this->assertSame([], $domainSentence->getWords());
    }
}
