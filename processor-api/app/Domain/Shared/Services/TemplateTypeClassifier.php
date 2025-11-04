<?php
namespace App\Domain\Shared\Services;

use App\Domain\Shared\Enums\SavedListType;

class TemplateTypeClassifier
{
    public function isKnownType(SavedListType $type): bool
    {
        return in_array($type, [
            SavedListType::KNOWNRADICALS,
            SavedListType::KNOWNKANJIS,
            SavedListType::KNOWNWORDS,
            SavedListType::KNOWNSENTENCES,
        ]);
    }

    public function isCustomListType(SavedListType $type): bool
    {
        return in_array($type, [
            SavedListType::RADICALS,
            SavedListType::KANJIS,
            SavedListType::WORDS,
            SavedListType::SENTENCES,
            SavedListType::ARTICLES,
        ]);
    }

    public function getBaseType(SavedListType $type): ?SavedListType
    {
        return match($type) {
            SavedListType::KNOWNRADICALS => SavedListType::RADICALS,
            SavedListType::KNOWNKANJIS => SavedListType::KANJIS,
            SavedListType::KNOWNWORDS => SavedListType::WORDS,
            SavedListType::KNOWNSENTENCES => SavedListType::SENTENCES,
            default => null,
        };
    }
}
