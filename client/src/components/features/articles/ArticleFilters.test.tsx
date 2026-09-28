// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ArticleFacetResource } from '@/api/generated/model/articleFacetResource';
import {
	parseArticleListSearchParams,
	type ArticleListFilterState,
} from '@/routes/ArticlesList/articleListSearchParams';
import { choose, click, controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import ArticleFilters from './ArticleFilters';

const facets: ArticleFacetResource[] = [
	{
		key: 'jlpt_levels',
		label: 'JLPT level',
		type: 'multi',
		values: [{ key: 'n5', label: 'N5', count: 12, selected: false }],
	},
	{
		key: 'hashtag_ids',
		label: 'Hashtags',
		type: 'multi',
		values: [{ key: '7', label: 'grammar', count: 3, selected: false }],
	},
];

const callbacks = () => ({
	onSearch: vi.fn(),
	onToggleJlptLevel: vi.fn(),
	onToggleHashtag: vi.fn(),
	onSortChange: vi.fn(),
	onReset: vi.fn(),
});

let view: Awaited<ReturnType<typeof renderWithAct>>;
let handlers: ReturnType<typeof callbacks>;

const mount = async (query = '') => {
	handlers = callbacks();
	const state: ArticleListFilterState = parseArticleListSearchParams(new URLSearchParams(query));
	view = await renderWithAct(<ArticleFilters state={state} facets={facets} {...handlers} />);
};

const button = (name: string | RegExp) =>
	Array.from(view.container.querySelectorAll('button')).find((element) =>
		typeof name === 'string' ? element.textContent === name : name.test(element.textContent ?? ''),
	);

beforeEach(() => mount());

afterEach(async () => {
	await view.unmount();
});

describe('ArticleFilters', () => {
	it('renders one search form with the search, the sort and the facet groups', () => {
		expect(view.container.querySelectorAll('form[role="search"]')).toHaveLength(1);
		expect(controlLabelled(view.container, 'Search articles')).not.toBeNull();
		expect(controlLabelled(view.container, 'Sort articles')).not.toBeNull();
		expect(view.container.querySelectorAll('fieldset')).toHaveLength(2);
	});

	it('searches with the trimmed term on Enter or the button', async () => {
		await view.flush(() => {
			typeInto(controlLabelled(view.container, 'Search articles'), '  grammar ');
			submitForm(view.container.querySelector('form') as HTMLFormElement);
		});

		expect(handlers.onSearch).toHaveBeenCalledWith('grammar');
	});

	it('holds a one-character search back and says why', async () => {
		await view.flush(() => typeInto(controlLabelled(view.container, 'Search articles'), 'a'));

		expect(view.container.textContent).toContain('Enter at least 2 characters.');
		expect((button('Search') as HTMLButtonElement).disabled).toBe(true);

		await view.flush(() => submitForm(view.container.querySelector('form') as HTMLFormElement));

		expect(handlers.onSearch).not.toHaveBeenCalled();
	});

	it('reports a sort change with the chosen sort value', async () => {
		await view.flush(() => choose(controlLabelled(view.container, 'Sort articles'), 'title_jp'));

		expect(handlers.onSortChange).toHaveBeenCalledWith('title_jp');
	});

	it('shows the facet counts and reports a toggle for each kind of facet', async () => {
		expect(button('N5 (12)')).toBeDefined();
		expect(button('#grammar (3)')).toBeDefined();

		await view.flush(() => click(button('N5 (12)') as HTMLButtonElement));
		await view.flush(() => click(button('#grammar (3)') as HTMLButtonElement));

		expect(handlers.onToggleJlptLevel).toHaveBeenCalledWith('n5');
		expect(handlers.onToggleHashtag).toHaveBeenCalledWith(7);
	});

	it('offers Reset only while a filter is applied, and calls it', async () => {
		expect(button('Reset')).toBeUndefined();

		await view.unmount();
		await mount('q=grammar');
		expect(button('Reset')).toBeDefined();

		await view.flush(() => click(button('Reset') as HTMLButtonElement));
		expect(handlers.onReset).toHaveBeenCalledTimes(1);
	});
});
