import en from './en.generated.json';
import type { TranslationKey } from './keys';

/** The code of the language the backend owns and every other bundle falls back to. */
export const SOURCE_LOCALE = 'en';

/** The English snapshot: the `en` bundle, and the floor under every other one. */
export const enSnapshot: Readonly<Record<TranslationKey, string>> = en;

export type Translate = (key: TranslationKey) => string;

const warnedKeys = new Set<string>();

/** Forgets which keys already warned. For tests: the set is module state. */
export const resetMissingKeyWarnings = () => warnedKeys.clear();

/**
 * Development builds only: a key that neither the bundle nor the snapshot holds is a migration
 * mistake (a typo cast past the types, or a key not exported yet), so say so once per key. Production
 * stays silent and shows the key.
 */
const warnMissingKey = (key: string) => {
	if (!import.meta.env.DEV || warnedKeys.has(key)) {
		return;
	}

	warnedKeys.add(key);
	console.warn(`[i18n] No translation for "${key}" in the bundle or the English snapshot.`);
};

/**
 * `t()` over one bundle. Lookup order: the bundle, then the English snapshot, then the key itself,
 * so a label is never blank (NFR-06). An empty bundle value counts as missing.
 */
export const createTranslate =
	(messages: Readonly<Record<string, string>>): Translate =>
	(key) => {
		const value = messages[key] || enSnapshot[key];

		if (value) {
			return value;
		}

		warnMissingKey(key);

		return key;
	};
