<?php

namespace App\Domain\Shared\Enums;

enum ObjectTemplateType: string
{
    case ARTICLE = 'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd';
    case ARTIST = '3105a1ce-c06f-4016-bf5b-b5287a023fd5';
    case LYRIC = '2ce2d586-169a-4e41-9cdd-251e93fde5e2';
    case RADICAL = 'e7367bcb-114e-4e89-b17f-810dfe87a3dc';
    case KANJI = '6cd99a38-fa88-4558-9f68-0f2162576f36';
    case WORD = 'd912962b-519e-4717-bcde-2cdd9fa00d37';
    case SENTENCE = '91e47d5f-f994-4a9a-b1fc-53d63393bb70';
    case LIST = '93edeaab-85d0-44ad-ba2d-4602ab4061ba';
    case POST = 'a4b78a83-f180-49b5-9f8a-39500cd8fabf';
    case COMMENT = '5ee9d6b7-aaae-4e0e-b63d-eae66ea49aef';

    public function label(): string
    {
        return match($this) {
            self::ARTICLE => 'Article',
            self::ARTIST => 'Artist',
            self::LYRIC => 'Lyric',
            self::RADICAL => 'Radical',
            self::KANJI => 'Kanji',
            self::WORD => 'Word',
            self::SENTENCE => 'Sentence',
            self::LIST => 'List',
            self::POST => 'Post',
            self::COMMENT => 'Comment',
        };
    }

       public function getTitle(): string
        {
            return match($this) {
                self::ARTICLE => 'article',
                self::ARTIST => 'artist',
                self::LYRIC => 'lyric',
                self::RADICAL => 'radical',
                self::KANJI => 'kanji',
                self::WORD => 'word',
                self::SENTENCE => 'sentence',
                self::LIST => 'list',
                self::POST => 'post',
                self::COMMENT => 'comment',
            };
        }
}
