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
        $rawSense = json_encode([
            [
                ['gloss', ['school']],
                ['gloss', ['academy']],
            ],
        ], JSON_THROW_ON_ERROR);

        $persistenceWord = new PersistenceWord([
            'id' => 10,
            'uuid' => 'word-uuid',
            'word' => '学校',
            'furigana' => 'がっこう',
            'jlpt' => 'N5',
            'word_type' => 'noun; education',
            'word_k_ele' => json_encode(['学校', '學校'], JSON_THROW_ON_ERROR),
            'furigana_r_ele' => json_encode(['がっこう'], JSON_THROW_ON_ERROR),
            'sense' => $rawSense,
        ]);

        $persistenceWord->id = 10;

        $word = (new WordMapper)->mapToDomain($persistenceWord);

        $this->assertInstanceOf(DomainWord::class, $word);
        $this->assertSame(10, $word->getIdValue());
        $this->assertSame('word-uuid', $word->getUuid()->value());
        $this->assertSame('学校', $word->getSurface());
        $this->assertSame('がっこう', $word->getFurigana());
        $this->assertSame('N5', $word->getJlpt());
        $this->assertSame(['noun', 'education'], $word->getWordTypes());
        $this->assertSame(['学校', '學校'], $word->getWritingElements());
        $this->assertSame(['がっこう'], $word->getReadingElements());
        $this->assertSame(['school', 'academy'], $word->getMeanings());
        $this->assertSame('noun; education', $word->getRawWordType());
        $this->assertSame('["学校","學校"]', $word->getRawWritingElements());
        $this->assertSame('["がっこう"]', $word->getRawReadingElements());
        $this->assertSame($rawSense, $word->getRawSense());
    }

    public function test_map_to_domain_keeps_non_json_legacy_fields_pragmatic(): void
    {
        $persistenceWord = new PersistenceWord([
            'id' => 11,
            'uuid' => 'word-uuid-2',
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
}
