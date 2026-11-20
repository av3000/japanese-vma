import type en from './en.generated.json';

/**
 * Every key the interface can translate, read from the English snapshot that `php artisan
 * translations:export-source` writes. `t()` accepts only these, so a mistyped key fails `npm run
 * typecheck` instead of rendering the key on screen. A key that only arrives at runtime (from an API,
 * say) is cast to this type once, at the boundary.
 */
export type TranslationKey = keyof typeof en;
