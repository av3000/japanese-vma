// @vitest-environment jsdom
import { act } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { click, typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { HeaderSearch } from './index';
import { rememberSearch } from './recentSearches';

const navigate = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigate };
});

let view: Awaited<ReturnType<typeof renderWithAct>>;

const mount = async () => {
	view = await renderWithAct(
		<MemoryRouter>
			<button type="button">Outside</button>
			<HeaderSearch />
		</MemoryRouter>,
	);
};

// The drawer's dialog is always mounted too, so every query is scoped to the panel's form.
const form = () => requireElement<HTMLFormElement>(view.container, 'form[role="search"]');
const input = () => requireElement<HTMLInputElement>(form(), 'input[role="combobox"]');
const panel = () => form().querySelector<HTMLElement>('[id$="-panel"]');
const chips = (label: string) => {
	const heading = Array.from(form().querySelectorAll('p')).find((p) => p.textContent === label);
	return Array.from(form().querySelectorAll<HTMLButtonElement>(`ul[aria-labelledby="${heading?.id}"] button`));
};
const chip = (label: string, name: string) => {
	const match = chips(label).find((button) => button.textContent?.startsWith(name));
	if (!match) throw new Error(`No "${name}" chip under "${label}"`);
	return match;
};
const options = () => Array.from(form().querySelectorAll<HTMLLIElement>('[role="listbox"] [role="option"]'));
const activeOption = () => {
	const id = input().getAttribute('aria-activedescendant');
	return id ? form().querySelector(`[id="${id}"]`) : null;
};

const focusInput = () => act(() => input().focus());
const key = (target: Element, name: string) =>
	act(() => {
		target.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }));
	});
const submit = () => act(() => form().requestSubmit());

beforeEach(async () => {
	navigate.mockClear();
	localStorage.clear();
});

afterEach(async () => {
	await view.unmount();
});

describe('HeaderSearchPanel', () => {
	it('is a closed combobox in a search landmark until the input is focused', async () => {
		await mount();

		expect(input().getAttribute('aria-expanded')).toBe('false');
		expect(input().getAttribute('aria-keyshortcuts')).toBe('/');
		expect(panel()).toBeNull();

		focusInput();

		expect(input().getAttribute('aria-expanded')).toBe('true');
		expect(panel()).not.toBeNull();
		expect(chips('Search in').map((button) => button.textContent)).toEqual([
			'Articles',
			'Kanji',
			'Words',
			'Sentences',
			'Radicals',
		]);
		expect(chip('Search in', 'Articles').getAttribute('aria-pressed')).toBe('true');
	});

	it('detects a single kanji: Kanji first as the best match, with the first row active', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');

		expect(chips('Search in')[0].textContent).toBe('Kanji Best match');
		expect(chips('Search in')[0].getAttribute('aria-pressed')).toBe('true');
		expect(options().map((option) => option.textContent)).toEqual([
			'Kanji for 水 · single character detected↵',
			'Articles with 水 in the title↵',
			'Words containing 水↵',
			'Sentences with 水↵',
			'Radicals matching 水↵',
		]);
		expect(input().getAttribute('aria-controls')).toBe(options()[0].parentElement?.id);
		expect(activeOption()).toBe(options()[0]);
		expect(options()[0].getAttribute('aria-selected')).toBe('true');
		expect(options()[0].querySelector('[lang="ja"]')?.textContent).toBe('水');
	});

	it('sends Latin text to Words without a best-match claim, in meaning order', async () => {
		await mount();
		focusInput();
		typeInto(input(), 'water');

		expect(form().textContent).not.toContain('Best match');
		expect(chips('Search in').map((button) => button.textContent)).toEqual([
			'Words',
			'Kanji',
			'Radicals',
			'Sentences',
			'Articles',
		]);
		expect(options()[0].textContent).toBe('Words containing water · searches English meanings↵');
		expect(options()[0].querySelector('[lang="ja"]')).toBeNull();
	});

	it('moves the active row with the arrow keys (wrapping) and runs it on Enter', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');

		key(input(), 'ArrowDown');
		expect(activeOption()).toBe(options()[1]);
		key(input(), 'ArrowUp');
		key(input(), 'ArrowUp');
		expect(activeOption()).toBe(options()[4]);
		key(input(), 'ArrowDown');
		key(input(), 'ArrowDown');
		submit();

		expect(navigate).toHaveBeenCalledWith('/articles?q=%E6%B0%B4');
		expect(input().value).toBe('');
		expect(input().getAttribute('aria-expanded')).toBe('false');
	});

	it('runs a row on click', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');
		click(options()[2]);

		expect(navigate).toHaveBeenCalledWith('/words?keyword=%E6%B0%B4');
	});

	it('lets a picked chip override detection until the query is cleared', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');
		click(chip('Search in', 'Sentences'));

		expect(chip('Search in', 'Sentences').getAttribute('aria-pressed')).toBe('true');
		expect(form().textContent).not.toContain('Best match');
		expect(options()[0].textContent).toBe('Sentences with 水↵');

		typeInto(input(), '');
		typeInto(input(), '水');

		expect(chips('Search in')[0].textContent).toBe('Kanji Best match');
	});

	it('adds the Kanji JLPT refine, a single choice with Any', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');

		expect(chips('Refine kanji · JLPT level').map((button) => button.textContent)).toEqual([
			'Any',
			'N5',
			'N4',
			'N3',
			'N2',
			'N1',
			'Uncommon',
		]);
		click(chip('Refine kanji · JLPT level', 'N5'));
		click(chip('Refine kanji · JLPT level', 'N4'));
		expect(chip('Refine kanji · JLPT level', 'N4').getAttribute('aria-pressed')).toBe('true');
		expect(chip('Refine kanji · JLPT level', 'N5').getAttribute('aria-pressed')).toBe('false');
		expect(chip('Refine kanji · JLPT level', 'Any').getAttribute('aria-pressed')).toBe('false');
		submit();

		expect(navigate).toHaveBeenCalledWith('/kanjis?keyword=%E6%B0%B4&jlpt=4');
	});

	it('adds the Articles JLPT refine as several toggles', async () => {
		await mount();
		focusInput();
		typeInto(input(), 'news');
		click(chip('Search in', 'Articles'));
		click(chip('Refine articles · JLPT levels', 'N3'));
		click(chip('Refine articles · JLPT levels', 'N5'));
		submit();

		expect(navigate).toHaveBeenCalledWith('/articles?q=news&jlpt_levels%5B%5D=n5&jlpt_levels%5B%5D=n3');
	});

	it('offers no refine for Words, Sentences or Radicals', async () => {
		await mount();
		focusInput();
		typeInto(input(), 'たべる');

		expect(form().textContent).not.toContain('Refine');
	});

	it('closes on Escape but keeps focus in the input, and reopens on ArrowDown', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');
		key(input(), 'Escape');

		expect(input().getAttribute('aria-expanded')).toBe('false');
		expect(input().getAttribute('aria-activedescendant')).toBeNull();
		expect(document.activeElement).toBe(input());
		expect(input().value).toBe('水');

		key(input(), 'ArrowDown');
		expect(input().getAttribute('aria-expanded')).toBe('true');
	});

	it('closes when focus leaves the form', async () => {
		await mount();
		focusInput();
		act(() => requireElement<HTMLButtonElement>(view.container, 'button').focus());

		expect(input().getAttribute('aria-expanded')).toBe('false');
	});

	it('shows recent searches for an empty query, defaults to the last scope, and re-runs one', async () => {
		rememberSearch('words', 'たべる');
		rememberSearch('kanji', '水');
		await mount();
		focusInput();

		const recent = chips('Recent searches');
		expect(recent.map((button) => button.textContent)).toEqual(['水 · Kanji', 'たべる · Words']);
		expect(chip('Search in', 'Kanji').getAttribute('aria-pressed')).toBe('true');
		click(recent[1]);

		expect(navigate).toHaveBeenCalledWith('/words?keyword=%E3%81%9F%E3%81%B9%E3%82%8B');
	});

	it('remembers a search, and opens the scope list unfiltered on an empty Enter', async () => {
		await mount();
		focusInput();
		typeInto(input(), '水');
		submit();
		focusInput();

		expect(chips('Recent searches').map((button) => button.textContent)).toEqual(['水 · Kanji']);
		submit();
		expect(navigate).toHaveBeenLastCalledWith('/kanjis');
	});

	it('focuses the input on "/" from outside a text field, but not while typing', async () => {
		await mount();
		const outside = requireElement<HTMLButtonElement>(view.container, 'button');
		act(() => outside.focus());
		key(outside, '/');

		expect(document.activeElement).toBe(input());

		typeInto(input(), 'a');
		key(input(), '/');
		expect(document.activeElement).toBe(input());
	});
});
