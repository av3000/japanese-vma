// @vitest-environment jsdom
import type { ReactElement } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import RadicalsList from './RadicalsList';
import SentencesList from './SentencesList';

/*
 * Sentences and Radicals moved from SearchBarSentences/SearchBarRadicals to the shared FilterBar.
 * The URL and the request must stay as they were: the same `keyword` param, trimmed, applied on
 * Enter or the button. Only `per_page` changed, from 10 to 25 (UI-DICT-00, #427).
 */

const setSearchParamsMock = vi.fn();
let searchParams = new URLSearchParams();
// vi.mock factories are hoisted above module code, so the shared hook stub is hoisted with them.
const { emptyQuery, captured } = vi.hoisted(() => {
	const captured: { filters?: Record<string, unknown> } = {};
	const emptyQuery =
		(key: 'sentences' | 'radicals') =>
		({ filters }: { filters: Record<string, unknown> }) => {
			captured.filters = filters;

			return {
				[key]: [],
				total: 0,
				isLoading: false,
				isFetchingNextPage: false,
				hasNextPage: false,
				fetchNextPage: () => undefined,
				error: null,
				isError: false,
			};
		};

	return { emptyQuery, captured };
});

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return { ...actual, useSearchParams: () => [searchParams, setSearchParamsMock] };
});
vi.mock('@/api/sentences/hooks/useInfiniteSentences', () => ({ useInfiniteSentences: emptyQuery('sentences') }));
vi.mock('@/api/radicals/hooks/useInfiniteRadicals', () => ({ useInfiniteRadicals: emptyQuery('radicals') }));
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: false }) }));

const LISTS: Array<{ name: string; element: () => ReactElement; landmark: string; input: string }> = [
	{
		name: 'SentencesList',
		element: () => <SentencesList />,
		landmark: 'Sentence filters',
		input: 'Search sentences by keyword',
	},
	{
		name: 'RadicalsList',
		element: () => <RadicalsList />,
		landmark: 'Radical filters',
		input: 'Search radicals by keyword',
	},
];

describe.each(LISTS)('$name filters', ({ element, landmark, input }) => {
	let view: Awaited<ReturnType<typeof renderWithAct>>;

	const mount = async (query = '') => {
		searchParams = new URLSearchParams(query);
		view = await renderWithAct(element());
	};
	const keywordInput = () => controlLabelled<HTMLInputElement>(view.container, input);
	const form = () => view.container.querySelector('form') as HTMLFormElement;
	const appliedParams = () => (setSearchParamsMock.mock.calls.at(-1)?.[0] as URLSearchParams).toString();

	beforeEach(() => {
		setSearchParamsMock.mockClear();
		captured.filters = undefined;
	});

	afterEach(async () => {
		await view.unmount();
	});

	it('names the search landmark', async () => {
		await mount();

		expect(view.container.querySelector('form[role="search"]')?.getAttribute('aria-label')).toBe(landmark);
	});

	it('writes a trimmed keyword to the same URL param, only on submit', async () => {
		await mount();

		await view.flush(() => typeInto(keywordInput(), '  water '));
		expect(setSearchParamsMock).not.toHaveBeenCalled();

		await view.flush(() => submitForm(form()));
		expect(appliedParams()).toBe('keyword=water');
	});

	it('writes no params for an empty search', async () => {
		await mount('keyword=water');

		await view.flush(() => {
			typeInto(keywordInput(), '   ');
			submitForm(form());
		});

		expect(appliedParams()).toBe('');
	});

	it('resets the input to the URL when it changes, such as on browser back', async () => {
		await mount('keyword=water');
		await view.flush(() => typeInto(keywordInput(), 'half typed'));

		searchParams = new URLSearchParams('keyword=fire');
		await view.rerender(element());

		expect(keywordInput().value).toBe('fire');
	});

	it('fills the input from the URL and sends the same request params', async () => {
		await mount('keyword=%20water%20');

		expect(keywordInput().value).toBe('water');
		expect(captured.filters).toEqual({ keyword: 'water', per_page: 25 });
	});

	it('sends no keyword when the URL has none', async () => {
		await mount();

		expect(captured.filters).toEqual({ per_page: 25 });
	});
});
