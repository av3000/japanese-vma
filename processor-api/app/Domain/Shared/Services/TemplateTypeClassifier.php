<?php
namespace App\Domain\Shared\Services;

use App\Domain\Shared\Enums\ObjectTemplateType;

class TemplateTypeClassifier
{
    public function isKnownType(ObjectTemplateType $type): bool
    {
        return in_array($type, [
            ObjectTemplateType::KNOWNRADICALS,
            ObjectTemplateType::KNOWNKANJIS,
            ObjectTemplateType::KNOWNWORDS,
            ObjectTemplateType::KNOWNSENTENCES,
        ]);
    }

    public function isCustomListType(ObjectTemplateType $type): bool
    {
        return in_array($type, [
            ObjectTemplateType::RADICALS,
            ObjectTemplateType::KANJIS,
            ObjectTemplateType::WORDS,
            ObjectTemplateType::SENTENCES,
            ObjectTemplateType::ARTICLES,
        ]);
    }

    public function getBaseType(ObjectTemplateType $type): ?ObjectTemplateType
    {
        return match($type) {
            ObjectTemplateType::KNOWNRADICALS => ObjectTemplateType::RADICALS,
            ObjectTemplateType::KNOWNKANJIS => ObjectTemplateType::KANJIS,
            ObjectTemplateType::KNOWNWORDS => ObjectTemplateType::WORDS,
            ObjectTemplateType::KNOWNSENTENCES => ObjectTemplateType::SENTENCES,
            default => null,
        };
    }
}
