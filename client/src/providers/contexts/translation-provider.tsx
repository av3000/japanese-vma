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
	// language that turns out to be inactive would be a 404 and an error notice for nothing. The list
	// counts as known while it holds data, so a refetch that fails does not drop the learner to English.
	// A choice made this visit is checked against it again, so a language deactivated since is let go.
	const activeCodes = localesQuery.data ? locales.map((option) => option.code) : null;
	const requested = activeCodes
		? chosen && activeCodes.includes(chosen)
			? chosen
			: pickLocale({ stored, browserLanguages, activeCodes })
		: SOURCE_LOCALE;

	// Always stale: the app's five-minute default would serve a translator's old wording when the learner
	// switches away and back (NFR-02, AC-3 of #666). The cached bundle shows at once and the fresh one
	// replaces it. No refetch on window focus: only a language switch or a reload asks again.
	const bundleQuery = useTranslationShow(requested, {
		query: { enabled: requested !== SOURCE_LOCALE, staleTime: 0, refetchOnWindowFocus: false },
	});

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

	// A failed background refetch of a bundle already held keeps its data and is not an error to show.
	const failed = bundleQuery.isError && !bundleQuery.data && requested !== SOURCE_LOCALE;
	const error = React.useMemo(() => (failed ? { locale: requested } : null), [failed, requested]);
	const { refetch } = bundleQuery;

	React.useEffect(() => {
		document.documentElement.lang = shown.locale;
	}, [shown.locale]);

	const setLocale = React.useCallback(
		(code: string) => {
			if (!locales.some((option) => option.code === code)) {
				return;
			}

			storeLocale(code);

			// The same language again after a failure: nothing in the query key changes, so ask again.
			if (code === requested && error) {
				void refetch();
			}

			setChosen(code);
		},
		[locales, requested, error, refetch],
	);

	const t = React.useMemo(() => createTranslate(shown.messages), [shown.messages]);

	const value = React.useMemo<TranslationContextValue>(
		() => ({ t, locale: requested, displayedLocale: shown.locale, locales, setLocale, error }),
		[t, requested, shown.locale, locales, setLocale, error],
	);

	return <TranslationContext.Provider value={value}>{children}</TranslationContext.Provider>;
};
