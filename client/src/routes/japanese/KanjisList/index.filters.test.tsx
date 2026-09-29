// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { choose, controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import KanjisList from './index';

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

vi.mock('@/api/kanjis/hooks/useInfiniteKanjis', async () => {
	const actual = await vi.importActual<typeof import('@/api/kanjis/hooks/useInfiniteKanjis')>(
		'@/api/kanjis/hooks/useInfiniteKanjis',
	);

	return {
		...actual,
		useInfiniteKanjis: ({ filters }: { filters: Record<string, unknown> }) => {
			capturedFilters = filters;

			return {
				kanjis: [],
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
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

let view: Awaited<ReturnType<typeof renderWithAct>>;

const mount = async (query = '') => {
	searchParams = new URLSearchParams(query);
	view = await renderWithAct(<KanjisList />);
};

const form = () => view.container.querySelector('form') as HTMLFormElement;

const appliedParams = () => (setSearchParamsMock.mock.calls.at(-1)?.[0] as URLSearchParams).toString();

beforeEach(() => {
	setSearchParamsMock.mockClear();
	capturedFilters = undefined;
});

afterEach(async () => {
	await view.unmount();
});

describe('KanjisList filters', () => {
	it('offers All, N1 to N5 and Uncommon, with the same option values as before', async () => {
		await mount();

		const options = Array.from(controlLabelled<HTMLSelectElement>(view.container, 'JLPT level').options).map(
			(option) => [option.value, option.textContent],
		);

		expect(options).toEqual([
			['', 'All levels'],
			['1', 'N1'],
			['2', 'N2'],
			['3', 'N3'],
			['4', 'N4'],
			['5', 'N5'],
			['-', 'Uncommon'],
		]);
	});

	it('writes a trimmed keyword and a JLPT level to the same URL params as before', async () => {
		await mount();

		await view.flush(() => {
			typeInto(controlLabelled(view.container, 'Search kanji by keyword'), '  water ');
			choose(controlLabelled(view.container, 'JLPT level'), '5');
			submitForm(form());
		});

		expect(appliedParams()).toBe('keyword=water&jlpt=5');
	});

	it('writes the Uncommon level as "-"', async () => {
		await mount();

		await view.flush(() => {
			choose(controlLabelled(view.container, 'JLPT level'), '-');
			submitForm(form());
		});

		expect(appliedParams()).toBe('jlpt=-');
	});

	it('writes no params for an empty search', async () => {
		await mount('keyword=water&jlpt=5');

		await view.flush(() => {
			typeInto(controlLabelled(view.container, 'Search kanji by keyword'), '   ');
			choose(controlLabelled(view.container, 'JLPT level'), '');
			submitForm(form());
		});

		expect(appliedParams()).toBe('');
	});

	it('resets the form to the URL when it changes, such as on browser back', async () => {
		await mount('keyword=water&jlpt=5');
		await view.flush(() => typeInto(controlLabelled(view.container, 'Search kanji by keyword'), 'half typed'));

		searchParams = new URLSearchParams('keyword=fire&jlpt=4');
		await view.rerender(<KanjisList />);

		expect(controlLabelled<HTMLInputElement>(view.container, 'Search kanji by keyword').value).toBe('fire');
		expect(controlLabelled<HTMLSelectElement>(view.container, 'JLPT level').value).toBe('4');
	});

	it('fills the form from the URL and sends the same request params', async () => {
		await mount('keyword=water&jlpt=5');

		expect(controlLabelled<HTMLInputElement>(view.container, 'Search kanji by keyword').value).toBe('water');
		expect(controlLabelled<HTMLSelectElement>(view.container, 'JLPT level').value).toBe('5');
		expect(capturedFilters).toMatchObject({ keyword: 'water', jlpt: '5', per_page: 10 });
	});
});
