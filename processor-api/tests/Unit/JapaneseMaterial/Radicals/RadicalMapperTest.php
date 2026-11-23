<?php

namespace Tests\Unit\JapaneseMaterial\Radicals;

use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Infrastructure\Persistence\Models\Radical as PersistenceRadical;
use App\Infrastructure\Persistence\Repositories\KanjiMapper;
use App\Infrastructure\Persistence\Repositories\RadicalMapper;
use Illuminate\Support\Str;
use Tests\TestCase;

class RadicalMapperTest extends TestCase
{
    public function test_mapper_maps_radical_scalar_fields(): void
    {
        $uuid = (string) Str::uuid();
        $persistenceRadical = new PersistenceRadical([
            'radical' => '水',
            'strokes' => 4,
            'meaning' => 'water',
            'hiragana' => 'みず / mizu',
        ]);
        $persistenceRadical->id = 85;
        $persistenceRadical->uuid = $uuid;

        $mapper = new RadicalMapper(new KanjiMapper());

        $domainRadical = $mapper->mapToDomain($persistenceRadical);

        $this->assertSame(85, $domainRadical->getIdValue());
        $this->assertSame($uuid, $domainRadical->getUuid()->value());
        $this->assertSame('水', $domainRadical->getRadical());
        $this->assertSame(4, $domainRadical->getStrokes());
        $this->assertSame('water', $domainRadical->getMeaning());
        $this->assertSame('みず / mizu', $domainRadical->getHiragana());
        $this->assertSame([], $domainRadical->getKanjis());
    }

    public function test_mapper_maps_loaded_related_kanjis(): void
    {
        $persistenceRadical = new PersistenceRadical([
            'radical' => '一',
            'strokes' => 1,
            'meaning' => 'one',
            'hiragana' => 'いち / ichi',
        ]);
        $persistenceRadical->id = 1;
        $persistenceRadical->uuid = (string) Str::uuid();

        $persistenceKanji = new PersistenceKanji([
            'kanji' => '一',
            'onyomi' => 'イチ',
            'kunyomi' => 'ひと',
            'meaning' => 'one',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '1',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => '1',
            'radical_parts' => '一',
        ]);
        $persistenceKanji->id = 10;
        $persistenceKanji->uuid = (string) Str::uuid();

        $persistenceRadical->setRelation('kanjis', collect([$persistenceKanji]));

        $mapper = new RadicalMapper(new KanjiMapper());

        $domainRadical = $mapper->mapToDomain($persistenceRadical);

        $this->assertCount(1, $domainRadical->getKanjis());
        $this->assertSame('一', $domainRadical->getKanjis()[0]->getCharacter()->value());
    }
}
