<?php

namespace App\Domain\Articles\Actions\Processing;

use App\Http\Models\Kanji;

class ExtractKanjis
{
    public function execute(string $text): array
    {
        preg_match_all('/[\x{4E00}-\x{9FAF}]/u', $text, $matches);
        $kanjis = array_unique($matches[0]);

        return Kanji::whereIn('kanji', $kanjis)->pluck('id')->toArray();
    }
}
