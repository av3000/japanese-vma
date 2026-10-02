// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import WordsList from './index';

/*
 * The move from SearchBarWords to the shared FilterBar must leave the URL and the request
 * unchanged: the same `keyword` param, trimmed, applied on Enter or the button. Only `per_page`
 * changed, from 10 to 25 (UI-DICT-00, #427).
 */

const setSearchParamsMock = vi.fn();
let searchParams = new URLSearchParams();
let capturedFilters: Record<string, unknown> | undefined;

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return { ...actual, useSearchParams: () => [searchParams, setSearchParamsMock] };
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');

	return { ...actual, useQueryClient: () => ({ setQueryData: vi.fn() }) };
});

vi.mock('@/api/words/hooks/useInfiniteWords', async () => {
	const actual = await vi.importActual<typeof import('@/api/words/hooks/useInfiniteWords')>(
		'@/api/words/hooks/useInfiniteWords',
	);

	return {
		...actual,
		useInfiniteWords: ({ filters }: { filters: Record<string, unknown> }) => {
			capturedFilters = filters;

			return {
				words: [],
				total: 0,
				isPending: false,
				isFetchingNextPage: false,
				hasNextPage: false,
				error: null,
				isError: false,
				fetchNextPage: vi.fn(),
			};
		},
	};
});

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: false }) }));

let view: Awaited<ReturnType<typeof renderWithAct>>;

const mount = async (query = '') => {
	searchParams = new URLSearchParams(query);
	view = await renderWithAct(<WordsList />);
};

const keywordInput = () => controlLabelled<HTMLInputElement>(view.container, 'Search words by keyword');
const form = () => view.container.querySelector('form') as HTMLFormElement;
const appliedParams = () => (setSearchParamsMock.mock.calls.at(-1)?.[0] as URLSearchParams).toString();

beforeEach(() => {
	setSearchParamsMock.mockClear();
	capturedFilters = undefined;
});

afterEach(async () => {
	await view.unmount();
});

describe('WordsList filters', () => {
	it('names the search landmark', async () => {
		await mount();

		expect(view.container.querySelector('form[role="search"]')?.getAttribute('aria-label')).toBe('Word filters');
	});

	it('writes a trimmed keyword to the same URL param as before', async () => {
		await mount();

		await view.flush(() => {
			typeInto(keywordInput(), '  はんらん ');
			submitForm(form());
		});

		expect(appliedParams()).toBe(`keyword=${encodeURIComponent('はんらん')}`);
	});

	it('applies a typed keyword only on submit', async () => {
		await mount();

		await view.flush(() => typeInto(keywordInput(), 'water'));
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
		await view.rerender(<WordsList />);

		expect(keywordInput().value).toBe('fire');
	});

	it('fills the input from the URL and sends the same request params', async () => {
		await mount('keyword=%20water%20');

		expect(keywordInput().value).toBe('water');
		expect(capturedFilters).toEqual({ keyword: 'water', per_page: 25, include: 'viewer_catalogue_state' });
	});

	it('sends no keyword when the URL has none', async () => {
		await mount();

		expect(capturedFilters).toEqual({ per_page: 25, include: 'viewer_catalogue_state' });
	});
});
