<?php

declare(strict_types=1);

namespace App\Mcp\Support;

/**
 * Cuts a text to a number of characters, so one article cannot fill an assistant's context.
 *
 * Characters are counted with `mb_strlen`, never bytes: a Japanese character is three bytes, so a
 * byte limit would cut the text at a third of its size.
 */
final class BoundedText
{
    /**
     * @return array{0: string, 1: bool} The text, cut at the limit, and whether anything was cut.
     */
    public static function cut(string $text, int $limit): array
    {
        if (mb_strlen($text) <= $limit) {
            return [$text, false];
        }

        return [mb_substr($text, 0, $limit), true];
    }
}
