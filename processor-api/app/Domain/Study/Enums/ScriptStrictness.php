<?php

declare(strict_types=1);

namespace App\Domain\Study\Enums;

/**
 * Whether a typed kana answer must be in the script the field is defined by.
 * On'yomi is katakana and kun'yomi is hiragana; `strict` holds the learner to that,
 * `lenient` accepts either script. Word and radical readings are always lenient.
 */
enum ScriptStrictness: string
{
    case STRICT = 'strict';
    case LENIENT = 'lenient';
}
