<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Comments\Interfaces\Readers\CommentParentReaderInterface;
use App\Domain\Comments\DTOs\CommentParentDTO;
use Illuminate\Support\Facades\DB;

final class DatabaseCommentParentReader implements CommentParentReaderInterface
{
    /**
     * Table and label column per commentable type. Types without an entry (artists, lyrics) have
     * no table left and always resolve as missing.
     */
    private const SOURCES = [
        'ARTICLE' => ['articles', 'title_jp'],
        'LIST' => ['customlists', 'title'],
        'POST' => ['posts', 'title'],
        'SENTENCE' => ['japanese_tatoeba_sentences', 'content'],
        'KANJI' => ['japanese_kanji_bank_long', 'kanji'],
        'WORD' => ['japanese_word_bank_long', 'word'],
        'RADICAL' => ['japanese_radicals_bank_long', 'radical'],
    ];

    public function findParents(array $parents): array
    {
        $idsByType = [];
        $types = [];

        foreach ($parents as [$type, $id]) {
            $idsByType[$type->name][$id] = $id;
            $types[$type->name] = $type;
        }

        $resolved = [];

        foreach ($idsByType as $typeName => $ids) {
            $type = $types[$typeName];
            $found = [];

            if (isset(self::SOURCES[$typeName])) {
                [$table, $labelColumn] = self::SOURCES[$typeName];

                $found = DB::table($table)
                    ->whereIn('id', array_values($ids))
                    ->get(['id', 'uuid', "{$labelColumn} as label"])
                    ->keyBy('id');
            }

            foreach ($ids as $id) {
                $row = $found[$id] ?? null;

                $resolved[CommentParentDTO::key($type, $id)] = $row === null
                    ? CommentParentDTO::missing($type, $id)
                    : new CommentParentDTO($type, $id, $row->uuid === null ? null : (string) $row->uuid, (string) $row->label);
            }
        }

        return $resolved;
    }
}
