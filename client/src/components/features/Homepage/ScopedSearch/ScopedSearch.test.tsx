// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ScopedSearch, scopedSearchUrl } from './index';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const navigate = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigate };
});

const must = <T,>(element: T | null): T => {
	if (element === null) throw new Error('Expected element to be rendered');
	return element;
};

describe('scopedSearchUrl', () => {
	it.each([
		['articles', '/articles?q=%E6%B0%B4'],
		['kanji', '/kanjis?keyword=%E6%B0%B4'],
		['words', '/words?keyword=%E6%B0%B4'],
		['sentences', '/sentences?keyword=%E6%B0%B4'],
		['radicals', '/radicals?keyword=%E6%B0%B4'],
	] as const)('sends %s to its list with the trimmed keyword', (scope, url) => {
		expect(scopedSearchUrl(scope, '  水 ')).toBe(url);
	});

	it.each(['articles', 'kanji', 'words', 'sentences', 'radicals'] as const)(
		'opens the %s list unfiltered for an empty or whitespace-only keyword',
		(scope) => {
			expect(scopedSearchUrl(scope, '')).not.toContain('?');
			expect(scopedSearchUrl(scope, '   ')).not.toContain('?');
		},
	);

	it('passes a short Articles keyword through; the list enforces its own minimum', () => {
		expect(scopedSearchUrl('articles', 'a')).toBe('/articles?q=a');
	});
});

describe('ScopedSearch', () => {
	let container: HTMLDivElement;
	let root: Root;

	beforeEach(() => {
		navigate.mockClear();
		container = document.createElement('div');
		document.body.appendChild(container);
		root = createRoot(container);
		act(() => {
			root.render(
				<MemoryRouter>
					<ScopedSearch />
				</MemoryRouter>,
			);
		});
	});

	afterEach(() => {
		act(() => root.unmount());
		container.remove();
	});

	const radios = () => Array.from(container.querySelectorAll<HTMLInputElement>('input[type="radio"]'));
	const input = () => must(container.querySelector<HTMLInputElement>('input[type="search"]'));

	const type = (value: string) => {
		act(() => {
			const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;
			setter?.call(input(), value);
			input().dispatchEvent(new Event('input', { bubbles: true }));
		});
	};

	const submit = () => {
		act(() => {
			must(container.querySelector('form')).requestSubmit();
		});
	};

	it('is a search landmark with five scopes in one radio group, Articles first and selected', () => {
		expect(container.querySelector('form[role="search"]')).not.toBeNull();
		expect(radios().map((radio) => radio.value)).toEqual(['articles', 'kanji', 'words', 'sentences', 'radicals']);
		// One shared name is what gives native arrow-key movement and a single tab stop.
		expect(new Set(radios().map((radio) => radio.name)).size).toBe(1);
		expect(radios()[0].checked).toBe(true);
	});

	it('labels the input for the selected scope and never disables the button', () => {
		const label = must(container.querySelector<HTMLLabelElement>(`label[for="${input().id}"]`));
		expect(label.textContent).toBe('Search articles');

		act(() => radios()[1].click());

		expect(label.textContent).toBe('Search kanji');
		expect(input().placeholder).toBe('Search kanji');
		expect(must(container.querySelector<HTMLButtonElement>('button[type="submit"]')).disabled).toBe(false);
	});

	it('navigates to the selected scope with the trimmed keyword on submit', () => {
		act(() => radios()[2].click());
		type('  食べる  ');
		submit();

		expect(navigate).toHaveBeenCalledWith('/words?keyword=%E9%A3%9F%E3%81%B9%E3%82%8B');
	});

	it('opens the unfiltered list on an empty or whitespace-only submit', () => {
		submit();
		type('   ');
		submit();

		expect(navigate.mock.calls).toEqual([['/articles'], ['/articles']]);
	});
});
