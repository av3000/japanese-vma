<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\ContentImport\Enums\SourceTagKind;
use App\Infrastructure\Persistence\Models\ContentSource;
use App\Infrastructure\Persistence\Models\SourceTagMapping;
use Illuminate\Database\Seeder;

/**
 * Production-safe and idempotent: the starting allow-list from NHK genres and common NHK topics
 * (the `keywords` of an article's NewsArticle JSON-LD) to platform hashtags. Existing rows are
 * never overwritten, so an operator's edits survive re-seeding.
 *
 * Programme and series names NHK also files as topics (クローズアップ現代, 深掘りコンテンツ, ...)
 * are deliberately absent, as are place names: they say where, not what about.
 */
class NhkTagMappingSeeder extends Seeder
{
    /** NHK genre => hashtag */
    private const GENRES = [
        '社会' => '#社会',
        '政治' => '#政治',
        '経済' => '#経済',
        '国際' => '#国際',
        '科学・文化' => '#科学・文化',
        '暮らし' => '#暮らし',
        'スポーツ' => '#スポーツ',
        '気象・災害' => '#気象・災害',
    ];

    /** NHK topic name => hashtag */
    private const TOPICS = [
        '生成AI・人工知能' => '#AI',
        '北朝鮮情勢' => '#北朝鮮',
        '韓国' => '#韓国',
        '中国' => '#中国',
        'アメリカ' => '#アメリカ',
        'トランプ大統領' => '#アメリカ',
        'ウクライナ情勢' => '#ウクライナ',
        'ロシア' => '#ロシア',
        '中東情勢' => '#中東',
        'イスラエル' => '#中東',
        '物価高' => '#物価',
        '円相場' => '#為替',
        '株価' => '#株価',
        '日銀' => '#日銀',
        '選挙' => '#選挙',
        '国会' => '#国会',
        '事件・事故' => '#事件・事故',
        '裁判' => '#裁判',
        '地震' => '#地震',
        '台風' => '#台風',
        '気象' => '#天気',
        '医療' => '#医療',
        '新型コロナウイルス' => '#新型コロナ',
        '教育' => '#教育',
        '子ども' => '#子ども',
        '少子化' => '#少子化',
        '働き方' => '#働き方',
        '観光' => '#観光',
        '交通' => '#交通',
        '環境' => '#環境',
        '気候変動' => '#気候変動',
        '宇宙' => '#宇宙',
        'サッカー' => '#サッカー',
        '野球' => '#野球',
        '大相撲' => '#相撲',
    ];

    public function run(): void
    {
        $source = ContentSource::query()->where('key', ContentSourceSeeder::NHK_NEWS)->first();

        if ($source === null) {
            return;
        }

        foreach ([SourceTagKind::Genre->value => self::GENRES, SourceTagKind::Topic->value => self::TOPICS] as $kind => $map) {
            foreach ($map as $externalKey => $hashtag) {
                SourceTagMapping::query()->firstOrCreate(
                    ['content_source_id' => $source->id, 'kind' => $kind, 'external_key' => $externalKey],
                    ['hashtag' => $hashtag],
                );
            }
        }
    }
}
