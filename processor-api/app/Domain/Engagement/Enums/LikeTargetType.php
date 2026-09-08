<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Enums;

use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * The object templates that can be liked, as their legacy `template_id`.
 */
// Why a separate enum rather than ObjectTemplateType: only four of its ten templates
// have a target the toggle can prove exists and is visible, and the request contract,
// the target resolver, and the tests all need to read that subset from one place.
//
// Why int-backed on the legacy id: that is already this endpoint's wire vocabulary, so
// Rule::enum can emit the enum as a reusable OpenAPI component instead of an inline
// list of values. The class docblock above is what lands in api.json - keep it short.
enum LikeTargetType: int
{
    case ARTICLE = 1;

    // ObjectTemplateType has no CATALOGUE case; `customlists` is the catalogue store.
    case CATALOGUE = 8;
    case POST = 9;
    case COMMENT = 10;

    public function objectTemplateType(): ObjectTemplateType
    {
        return match ($this) {
            self::ARTICLE => ObjectTemplateType::ARTICLE,
            self::CATALOGUE => ObjectTemplateType::LIST,
            self::POST => ObjectTemplateType::POST,
            self::COMMENT => ObjectTemplateType::COMMENT,
        };
    }

    public function legacyId(): int
    {
        return $this->value;
    }
}
