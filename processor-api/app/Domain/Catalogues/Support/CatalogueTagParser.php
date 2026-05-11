<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\Support;

class CatalogueTagParser
{
    /**
     * @return array<string>
     */
    public static function parse(?string $tagString): array
    {
        if ($tagString === null || trim($tagString) === '') {
            return [];
        }

        preg_match_all('/(#\w+)/u', $tagString, $matches);

        if (empty($matches[0])) {
            return [];
        }

        return array_values(array_keys(array_count_values($matches[0])));
    }
}
