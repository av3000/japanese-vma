<?php

declare(strict_types=1);

namespace App\Domain\Translations\Models;

/**
 * A language the interface can be shown in. English is the source and the fallback: it is
 * always present and active, and its labels come from the code-owned catalogue.
 */
final readonly class TranslationLocale
{
    public const FALLBACK_CODE = 'en';

    public function __construct(
        private int $id,
        private string $code,
        private string $name,
        private bool $isActive,
    ) {
    }

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isFallback(): bool
    {
        return $this->code === self::FALLBACK_CODE;
    }
}
