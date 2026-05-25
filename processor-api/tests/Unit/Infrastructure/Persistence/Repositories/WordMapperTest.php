<?php

namespace Tests\Unit\Infrastructure\Persistence\Repositories;

use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
use App\Infrastructure\Persistence\Models\Word as PersistenceWord;
use App\Infrastructure\Persistence\Repositories\WordMapper;
use Tests\TestCase;

class WordMapperTest extends TestCase
{
    public function test_map_to_domain_parses_delimited_and_json_word_fields(): void
    {
        $rawWritingElements = json_encode(['学校', '學校'], JSON_THROW_ON_ERROR);
        $rawReadingElements = json_encode(['がっこう'], JSON_THROW_ON_ERROR);
        $rawSense = json_encode([
            [
                ['gloss', ['school']],
                ['gloss', ['academy']],
            ],
        ], JSON_THROW_ON_ERROR);

        $uuid = '11111111-1111-4111-8111-111111111111';

        $persistenceWord = new PersistenceWord([
            'id' => 10,
            'uuid' => $uuid,
            'word' => '学校',
            'furigana' => 'がっこう',
            'jlpt' => 'N5',
            'word_type' => 'noun; education',
            'word_k_ele' => $rawWritingElements,
            'furigana_r_ele' => $rawReadingElements,
            'sense' => $rawSense,
        ]);

        $persistenceWord->id = 10;

        $word = (new WordMapper)->mapToDomain($persistenceWord);

        $this->assertInstanceOf(DomainWord::class, $word);
        $this->assertSame(10, $word->getIdValue());
        $this->assertSame($uuid, $word->getUuid()->value());
        $this->assertSame('学校', $word->getSurface());
        $this->assertSame('がっこう', $word->getFurigana());
        $this->assertSame('N5', $word->getJlpt());
        $this->assertSame(['noun', 'education'], $word->getWordTypes());
        $this->assertSame(['学校', '學校'], $word->getWritingElements());
        $this->assertSame(['がっこう'], $word->getReadingElements());
        $this->assertSame(['school', 'academy'], $word->getMeanings());
        $this->assertSame('noun; education', $word->getRawWordType());
        $this->assertSame($rawWritingElements, $word->getRawWritingElements());
        $this->assertSame($rawReadingElements, $word->getRawReadingElements());
        $this->assertSame($rawSense, $word->getRawSense());
    }

    public function test_map_to_domain_keeps_non_json_legacy_fields_pragmatic(): void
    {
        $uuid = '22222222-2222-4222-8222-222222222222';

        $persistenceWord = new PersistenceWord([
            'id' => 11,
            'uuid' => $uuid,
            'word' => '勉強',
            'furigana' => 'べんきょう',
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => '勉強',
            'furigana_r_ele' => 'べんきょう',
            'sense' => 'study',
        ]);

        $persistenceWord->id = 11;

        $word = (new WordMapper)->mapToDomain($persistenceWord);

        $this->assertSame(['noun'], $word->getWordTypes());
        $this->assertSame(['勉強'], $word->getWritingElements());
        $this->assertSame(['べんきょう'], $word->getReadingElements());
        $this->assertSame([], $word->getMeanings());
        $this->assertSame('noun', $word->getRawWordType());
        $this->assertSame('勉強', $word->getRawWritingElements());
        $this->assertSame('べんきょう', $word->getRawReadingElements());
        $this->assertSame('study', $word->getRawSense());
    }

    public function test_map_to_domain_handles_nested_word_payload_shapes_without_array_to_string_errors(): void
    {
        $rawSense = json_encode([
            [
                ['pos', ['auxiliary verb']],
                ['s_inf', ['after the imperfective form of certain verbs and adjectives']],
                ['gloss', ['indicates speculation']],
            ],
            [
                ['gloss', ['indicates will']],
            ],
            [
                ['gloss', ['indicates invitation']],
            ],
        ], JSON_THROW_ON_ERROR);

        $rawReadingElements = json_encode([
            [
                ['reb', ['う']],
            ],
        ], JSON_THROW_ON_ERROR);

        $uuid = '33333333-3333-4333-8333-333333333333';

        $persistenceWord = new PersistenceWord([
            'id' => 12,
            'uuid' => $uuid,
            'word' => 'う',
            'furigana' => '-',
            'jlpt' => '-',
            'word_type' => 'auxiliary verb|',
            'word_k_ele' => '[]',
            'furigana_r_ele' => $rawReadingElements,
            'sense' => $rawSense,
        ]);

        $persistenceWord->id = 12;

        $word = (new WordMapper)->mapToDomain($persistenceWord);

        $this->assertSame(['auxiliary verb'], $word->getWordTypes());
        $this->assertSame([], $word->getWritingElements());
        $this->assertSame([], $word->getReadingElements());
        $this->assertSame([
            'indicates speculation',
            'indicates will',
            'indicates invitation',
        ], $word->getMeanings());
        $this->assertSame($rawReadingElements, $word->getRawReadingElements());
        $this->assertSame($rawSense, $word->getRawSense());
    }
}
