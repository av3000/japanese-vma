import * as React from 'react';
import { useTranslationLocales, useTranslationShow } from '@/api/generated/translation/translation';
import { pickLocale, readBrowserLanguages, readStoredLocale, storeLocale, type LocaleOption } from '@/i18n/locale';
import { createTranslate, enSnapshot, SOURCE_LOCALE, type Translate } from '@/i18n/translate';

/** The language shown: its code, and the labels it holds. */
interface Bundle {
	locale: string;
	messages: Readonly<Record<string, string>>;
}

export interface TranslationContextValue {
	/** Looks a label up by key. Only keys from the English snapshot type-check. */
	t: Translate;
	/** The language the learner picked, which is the one the selector shows. */
	locale: string;
	/** The language the labels are in. It trails `locale` while a bundle loads, and stays behind if the load failed. */
	displayedLocale: string;
	/** The active languages, English first. A single entry means there is nothing to choose. */
	locales: readonly LocaleOption[];
	/** Switches language without a reload and remembers the choice. */
	setLocale: (code: string) => void;
	/** Set when the language the learner asked for could not be loaded; the labels stay as they were. */
	error: { locale: string } | null;
}

const englishOnly: LocaleOption[] = [{ code: SOURCE_LOCALE, name: 'English' }];

const defaultValue: TranslationContextValue = {
	t: createTranslate(enSnapshot),
	locale: SOURCE_LOCALE,
	displayedLocale: SOURCE_LOCALE,
	locales: englishOnly,
	setLocale: () => undefined,
	error: null,
};

/**
 * Defaults to English from the snapshot, so a component test or a story needs no provider and no
 * request to render real labels.
 */
export const TranslationContext = React.createContext<TranslationContextValue>(defaultValue);

const englishBundle: Bundle = { locale: SOURCE_LOCALE, messages: enSnapshot };

/**
 * The only place the app talks to the translation endpoints (FR-12). English comes from the snapshot
 * with no request. Another language is asked for only once the locale list shows it is active, and
 * the labels already on screen stay until its bundle arrives or the request fails (NFR-06).
 */
export const TranslationProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
	// What the learner and the browser said before the list arrived; read once, not on every render.
	const [stored] = React.useState(readStoredLocale);
	const [browserLanguages] = React.useState(readBrowserLanguages);
	const [chosen, setChosen] = React.useState<string | null>(null);

	const localesQuery = useTranslationLocales();

	const locales = React.useMemo<LocaleOption[]>(() => {
		const items = localesQuery.data?.items ?? [];
		const source = items.find((item) => item.code === SOURCE_LOCALE);

		return [
			{ code: SOURCE_LOCALE, name: source?.name ?? 'English' },
			...items.filter((item) => item.code !== SOURCE_LOCALE).map(({ code, name }) => ({ code, name })),
		];
	}, [localesQuery.data]);

	// Until the list says which languages are active, nothing but English is requested: asking for a
	// language that turns out to be inactive would be a 404 and an error notice for nothing.
	const requested = localesQuery.isSuccess
		? (chosen ?? pickLocale({ stored, browserLanguages, activeCodes: locales.map((option) => option.code) }))
		: SOURCE_LOCALE;

	const bundleQuery = useTranslationShow(requested, { query: { enabled: requested !== SOURCE_LOCALE } });

	const fresh: Bundle | null =
		requested === SOURCE_LOCALE
			? englishBundle
			: bundleQuery.isSuccess
				? { locale: requested, messages: bundleQuery.data.messages }
				: null;

	// The bundle on screen is state, not a query result: a query that is loading or failed holds no
	// data for the new key, but the labels must not blank or change language.
	const [shown, setShown] = React.useState<Bundle>(englishBundle);

	if (fresh && fresh.messages !== shown.messages) {
		setShown(fresh);
	}

	const error = bundleQuery.isError && requested !== SOURCE_LOCALE ? { locale: requested } : null;

	React.useEffect(() => {
		document.documentElement.lang = shown.locale;
	}, [shown.locale]);

	const setLocale = React.useCallback(
		(code: string) => {
			if (!locales.some((option) => option.code === code)) {
				return;
			}

			storeLocale(code);

			setChosen(code);
		},
		[locales],
	);

	const t = React.useMemo(() => createTranslate(shown.messages), [shown.messages]);

	const value = React.useMemo<TranslationContextValue>(
		() => ({ t, locale: requested, displayedLocale: shown.locale, locales, setLocale, error }),
		// `error` is a fresh object each render; its language is what changes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[t, requested, shown.locale, locales, setLocale, error?.locale],
	);

	return <TranslationContext.Provider value={value}>{children}</TranslationContext.Provider>;
};
