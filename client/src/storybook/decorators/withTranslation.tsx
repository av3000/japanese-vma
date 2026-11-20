import * as React from 'react';
import type { Decorator } from '@storybook/react-vite';
import { fn } from 'storybook/test';
import type { LocaleOption } from '@/i18n/locale';
import { createTranslate, enSnapshot } from '@/i18n/translate';
import { TranslationContext, type TranslationContextValue } from '@/providers/contexts/translation-provider';

/**
 * - `english`: only English is active, so no selector (the default: no decorator at all).
 * - `two`: English and Danish are active.
 * - `error`: Danish was picked, its bundle failed, and the English labels stayed.
 * - `danish`: Danish is shown, with only the two keys in `danishBundle` translated, as when a translator
 *   has saved two values: every other label falls back to English.
 */
export type TranslationScenario = 'english' | 'two' | 'error' | 'danish';

/** A scenario name, or fields to override. */
export type TranslationParameter = TranslationScenario | Partial<TranslationContextValue>;

/** Called when a story picks a language; cleared before each story, so a `play` can assert on it. */
export const translationSpies = { setLocale: fn() };

export const storyLocales: readonly LocaleOption[] = [
	{ code: 'en', name: 'English' },
	{ code: 'da', name: 'Dansk' },
];

/** The two values the translator saved in Filament; `t()` falls back to English for the rest. */
export const danishBundle = { 'account.log_in': 'Log ind', 'auth.login.submit': 'Log på' };

const base: TranslationContextValue = {
	t: createTranslate(enSnapshot),
	locale: 'en',
	displayedLocale: 'en',
	locales: storyLocales.slice(0, 1),
	setLocale: translationSpies.setLocale,
	error: null,
};

export const translationScenarios: Record<TranslationScenario, TranslationContextValue> = {
	english: base,
	two: { ...base, locales: storyLocales },
	error: { ...base, locale: 'da', locales: storyLocales, error: { locale: 'da' } },
	danish: {
		...base,
		t: createTranslate(danishBundle),
		locale: 'da',
		displayedLocale: 'da',
		locales: storyLocales,
	},
};

export const resolveTranslation = (parameter: TranslationParameter | undefined): TranslationContextValue => {
	if (typeof parameter === 'string') {
		return translationScenarios[parameter];
	}

	return { ...base, ...parameter };
};

/**
 * Provides `TranslationContext` with a plain value: never the real `TranslationProvider`, so no
 * request and no storage read. The picked language follows the select, so a story can be operated;
 * `setLocale` is a spy. Stories without `parameters.translation` get the English default.
 */
const StoryTranslation: React.FC<{ value: TranslationContextValue; children: React.ReactNode }> = ({
	value,
	children,
}) => {
	const [locale, setLocale] = React.useState(value.locale);

	const merged = React.useMemo<TranslationContextValue>(
		() => ({
			...value,
			locale,
			setLocale: (code) => {
				value.setLocale(code);
				setLocale(code);
			},
		}),
		[value, locale],
	);

	return <TranslationContext.Provider value={merged}>{children}</TranslationContext.Provider>;
};

export const withTranslation: Decorator = (Story, { id, parameters }) => {
	const parameter = parameters.translation as TranslationParameter | undefined;

	if (parameter === undefined) {
		return <Story />;
	}

	return (
		<StoryTranslation key={id} value={resolveTranslation(parameter)}>
			<Story />
		</StoryTranslation>
	);
};
