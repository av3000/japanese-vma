// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createTranslate, enSnapshot } from '@/i18n/translate';
import { TranslationContext, type TranslationContextValue } from '@/providers/contexts/translation-provider';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { LanguageSelector } from './';

const setLocale = vi.fn();

const value = (overrides: Partial<TranslationContextValue> = {}): TranslationContextValue => ({
	t: createTranslate(enSnapshot),
	locale: 'en',
	displayedLocale: 'en',
	locales: [
		{ code: 'en', name: 'English' },
		{ code: 'da', name: 'Dansk' },
	],
	setLocale,
	error: null,
	...overrides,
});

const mount = (context: TranslationContextValue) =>
	renderWithAct(
		<TranslationContext.Provider value={context}>
			<LanguageSelector />
		</TranslationContext.Provider>,
	);

afterEach(() => {
	setLocale.mockReset();
	document.body.innerHTML = '';
});

describe('LanguageSelector', () => {
	it('renders nothing while English is the only active language', async () => {
		const view = await mount(value({ locales: [{ code: 'en', name: 'English' }] }));

		expect(view.container.innerHTML).toBe('');
		await view.unmount();
	});

	it('is a labelled select that names the current language as text', async () => {
		const view = await mount(value({ locale: 'da', displayedLocale: 'da' }));

		const select = requireElement<HTMLSelectElement>(view.container, 'select');
		const label = requireElement<HTMLLabelElement>(view.container, `label[for="${select.id}"]`);

		expect(label.textContent).toBe('Language');
		expect(select.value).toBe('da');
		expect(select.selectedOptions[0]?.textContent).toBe('Dansk');
		expect([...select.options].map((option) => option.textContent)).toEqual(['English', 'Dansk']);
		expect(requireElement(view.container, 'option[value="da"]').getAttribute('lang')).toBe('da');
		await view.unmount();
	});

	it('asks for the picked language', async () => {
		const view = await mount(value());
		const select = requireElement<HTMLSelectElement>(view.container, 'select');

		await view.flush(() => {
			select.value = 'da';
			select.dispatchEvent(new Event('change', { bubbles: true }));
		});

		expect(setLocale).toHaveBeenCalledWith('da');
		await view.unmount();
	});

	it('keeps an empty polite status region, and fills it when a language failed to load', async () => {
		const view = await mount(value());
		const status = requireElement<HTMLElement>(view.container, '[role="status"]');

		expect(status.textContent).toBe('');

		await view.rerender(
			<TranslationContext.Provider value={value({ locale: 'da', error: { locale: 'da' } })}>
				<LanguageSelector />
			</TranslationContext.Provider>,
		);

		// The same element, so a screen reader announces the text when it appears.
		expect(requireElement<HTMLElement>(view.container, '[role="status"]')).toBe(status);
		expect(status.textContent).toBe("Couldn't load that language. Showing English.");
		// The select names the language on screen, so picking Dansk again is a change that retries it.
		expect(requireElement<HTMLSelectElement>(view.container, 'select').value).toBe('en');
		await view.unmount();
	});
});
