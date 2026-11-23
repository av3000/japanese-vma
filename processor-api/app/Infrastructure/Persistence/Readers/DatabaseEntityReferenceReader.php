<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Engagement\Interfaces\Readers\EntityReferenceReaderInterface;
use App\Domain\Shared\DTOs\EntityReferenceDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Support\Facades\DB;

final class DatabaseEntityReferenceReader implements EntityReferenceReaderInterface
{
    public function find(array $references): array
    {
        $idsByType = [];
        $types = [];

        foreach ($references as [$type, $id]) {
            $idsByType[$type->name][$id] = $id;
            $types[$type->name] = $type;
        }

        $resolved = [];

        foreach ($idsByType as $typeName => $ids) {
            $type = $types[$typeName];
            $found = [];

            $source = self::source($type);

            if ($source !== null) {
                [$table, $labelColumn] = $source;

                $found = DB::table($table)
                    ->whereIn('id', array_values($ids))
                    ->get(['id', 'uuid', "{$labelColumn} as label"])
                    ->keyBy('id');
            }

            foreach ($ids as $id) {
                $row = $found[$id] ?? null;

                $resolved[EntityReferenceDTO::key($type, $id)] = $row === null
                    ? EntityReferenceDTO::missing($type, $id)
                    : new EntityReferenceDTO($type, $id, $row->uuid === null ? null : (string) $row->uuid, (string) $row->label);
            }
        }

        return $resolved;
    }

    /**
     * Table and label column per referenced type. Artists, lyrics and comments have no table to
     * label from and always resolve as missing.
     *
     * @return array{0: string, 1: string}|null
     */
    private static function source(ObjectTemplateType $type): ?array
    {
        return match ($type) {
            ObjectTemplateType::ARTICLE => ['articles', 'title_jp'],
            ObjectTemplateType::LIST => ['customlists', 'title'],
            ObjectTemplateType::POST => ['posts', 'title'],
            ObjectTemplateType::SENTENCE => ['japanese_tatoeba_sentences', 'content'],
            ObjectTemplateType::KANJI => ['japanese_kanji_bank_long', 'kanji'],
            ObjectTemplateType::WORD => ['japanese_word_bank_long', 'word'],
            ObjectTemplateType::RADICAL => ['japanese_radicals_bank_long', 'radical'],
            ObjectTemplateType::ARTIST, ObjectTemplateType::LYRIC, ObjectTemplateType::COMMENT => null,
        };
    }
}
