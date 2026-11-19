import { SOURCE_LOCALE } from './translate';

/** Where the learner's choice is kept between visits. */
export const LOCALE_STORAGE_KEY = 'jvma.locale';

/** A language the learner can pick: the code the API serves, and its own name for it ("Dansk"). */
export interface LocaleOption {
	code: string;
	name: string;
}

/** `da-DK` and `da_DK` both become `da`. Codes are compared in lower case. */
export const baseLanguage = (tag: string) => tag.split(/[-_]/)[0].toLowerCase();

interface PickLocaleInput {
	/** The persisted choice, if there is one. */
	stored: string | null;
	/** `navigator.languages`, most preferred first. */
	browserLanguages: readonly string[];
	/** The codes of the active locales. */
	activeCodes: readonly string[];
}

/**
 * FR-13: the persisted choice while that locale is still active, then the first browser language
 * whose base language is active, then English. Returns a code from `activeCodes`, or English.
 */
export const pickLocale = ({ stored, browserLanguages, activeCodes }: PickLocaleInput): string => {
	const active = new Map(activeCodes.map((code) => [code.toLowerCase(), code]));

	const storedCode = stored ? active.get(stored.toLowerCase()) : undefined;

	if (storedCode) {
		return storedCode;
	}

	for (const tag of browserLanguages) {
		const match = active.get(baseLanguage(tag));

		if (match) {
			return match;
		}
	}

	return SOURCE_LOCALE;
};

/** The persisted choice, or null when there is none or storage is unavailable (private mode, blocked). */
export const readStoredLocale = (): string | null => {
	try {
		return window.localStorage.getItem(LOCALE_STORAGE_KEY);
	} catch {
		return null;
	}
};

/** Persists the choice. A storage failure is not the learner's problem: the choice still applies for this visit. */
export const storeLocale = (code: string) => {
	try {
		window.localStorage.setItem(LOCALE_STORAGE_KEY, code);
	} catch {
		// Storage is unavailable; nothing to do.
	}
};

/** `navigator.languages`, or an empty list outside a browser. */
export const readBrowserLanguages = (): readonly string[] =>
	typeof navigator === 'undefined' ? [] : (navigator.languages ?? [navigator.language]).filter(Boolean);
