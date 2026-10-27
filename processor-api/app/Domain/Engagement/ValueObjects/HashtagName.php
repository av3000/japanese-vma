<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

use InvalidArgumentException;
use Normalizer;

/**
 * A hashtag as people type it, plus the key that decides whether two tags are the same.
 *
 * The display form is kept as entered (trimmed). The key folds compatibility characters with
 * NFKC, so full-width `＃ＪＬＰＴ` and half-width `#JLPT` meet, and then lower-cases, so `#JLPT` and
 * `#jlpt` meet too.
 */
final readonly class HashtagName
{
    public const MAX_LENGTH = 255;

    public string $display;

    public string $key;

    public function __construct(string $value)
    {
        $display = trim($value);

        if ($display === '') {
            throw new InvalidArgumentException('A hashtag cannot be empty.');
        }

        if (mb_strlen($display) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('A hashtag cannot be longer than '.self::MAX_LENGTH.' characters.');
        }

        $this->display = $display;
        $this->key = self::keyFor($display);
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    /**
     * The key without the validation from(). Writes use from() and its $key. Only two readers call
     * this directly: the saving hook on the legacy Http-layer Uniquehashtag model and the panel's
     * hashtag search (the Uniquehashtag `matching` scope), which must accept any typed text.
     */
    public static function keyFor(string $value): string
    {
        $folded = Normalizer::normalize(trim($value), Normalizer::FORM_KC);

        return mb_strtolower($folded === false ? trim($value) : $folded);
    }

    public function sameKeyAs(self $other): bool
    {
        return $this->key === $other->key;
    }
}
