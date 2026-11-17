<?php

declare(strict_types=1);

namespace App\Application\Translations\Interfaces\Repositories;

/**
 * Stored non-English values: at most one per (locale, key). It stores and returns what it is told;
 * which of them count (non-empty, key still in English) is the resolver's decision.
 */
interface TranslationOverrideRepositoryInterface
{
    /**
     * Every stored override of one locale, in a single query, orphans and blank values included.
     *
     * @return array<string, string> Key to stored value.
     */
    public function overridesFor(int $localeId): array;

    /** Create the override, or replace its value and author when one exists for this (locale, key). */
    public function upsert(int $localeId, string $key, string $value, ?int $userId): void;

    /** Remove the override. Nothing happens when there is none. */
    public function delete(int $localeId, string $key): void;
}
