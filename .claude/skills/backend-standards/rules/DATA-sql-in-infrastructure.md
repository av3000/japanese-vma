---
title: SQL Lives in Infrastructure; PostgreSQL Only
impact: MEDIUM
impactDescription: queries are in one layer and can use PostgreSQL features safely
tags: data, sql, postgresql, infrastructure
---

## SQL Lives in Infrastructure; PostgreSQL Only

**Impact: MEDIUM (queries are in one layer and can use PostgreSQL features safely)**

The backend targets PostgreSQL only (CI, compose and production), so PostgreSQL-specific SQL (`ILIKE`, `COUNT(*) FILTER (WHERE ...)`, partial indexes) is allowed. It lives in `app/Infrastructure` (and in the Filament admin panel's own table queries, which sit outside the layered core). `app/Domain`, `app/Application` and `app/Http/v1` contain no SQL, no `DB::` calls and no query builders. Known debt: `LoadEntityStatsAction`, `LoadCommentsAction`, `LoadEntityHashtagsAction`, `UpdateArticleHashtagsAction` and `CleanupArticleHashtagsAction` still use `DB::table` in Application.

**Incorrect (`app/Application/Engagement/Actions/LoadEntityStatsAction.php`):**

```php
// TODO: use Repository pattern for stats
$likes = DB::table('likes')
    ->where('template_id', $templateId)
    ->whereIn('real_object_id', $entityIds)
    ->groupBy('real_object_id')
    ->selectRaw('real_object_id, COUNT(*) as ' . self::COUNT_ALIAS)
    ->pluck(self::COUNT_ALIAS, 'real_object_id');
```

**Correct (`Infrastructure/Persistence/Readers/ArticleListFilterBuilder` and `JlptLevelFacetCounter`):**

```php
/**
 * PostgreSQL LIKE is case-sensitive, unlike the MySQL collation used before
 * 041cfbf. ILIKE restores case-insensitive title matching.
 */
private const SEARCH_OPERATOR = 'ILIKE';

$selections[] = "COUNT(*) FILTER (WHERE {$level->column()} > 0) AS {$level->value}_count";
```

Source: `.ai/guidelines/backend-v1-architecture.md`.
