// @vitest-environment jsdom
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { DictionaryListPage, type DictionaryListQuery } from './DictionaryListPage';

const query = (overrides: Partial<DictionaryListQuery> = {}): DictionaryListQuery => ({
	total: 60,
	isPending: false,
	isError: false,
	hasNextPage: true,
	isFetchingNextPage: false,
	fetchNextPage: vi.fn(),
	refetch: vi.fn(),
	...overrides,
});

const page = (props: { itemCount?: number; query?: DictionaryListQuery; keyword?: string; extraMeta?: string[] }) => (
	<DictionaryListPage
		title="Words"
		noun="words"
		keyword={props.keyword ?? ''}
		extraMeta={props.extraMeta}
		itemCount={props.itemCount ?? 25}
		query={props.query ?? query()}
		filters={<div>filters</div>}
	>
		{({ loading, empty }) => <p>{loading ? 'loading' : `table:${String(empty.title)}`}</p>}
	</DictionaryListPage>
);

describe('DictionaryListPage', () => {
	it('joins the count, the keyword and the extra filters into one meta line', () => {
		const html = renderToStaticMarkup(page({ keyword: '水', extraMeta: ['JLPT: N5'] }));

		expect(html).toContain('Showing 25 of 60 · keyword: 水 · JLPT: N5');
	});

	it('shows no meta and no Load more until the first page arrives', () => {
		const html = renderToStaticMarkup(page({ itemCount: 0, query: query({ isPending: true }) }));

		expect(html).toContain('loading');
		expect(html).not.toContain('Showing');
		expect(html).not.toContain('Load more');
	});

	it('hands the table a keyword-aware empty panel and drops Load more when nothing matched', () => {
		const html = renderToStaticMarkup(page({ itemCount: 0, query: query({ total: 0 }), keyword: 'xyz' }));

		expect(html).toContain('0 results · keyword: xyz');
		expect(html).not.toContain('Load more');
	});

	it('shows a fixed message and a retry, never the raw error text, when nothing loaded', async () => {
		const refetch = vi.fn();
		const view = await renderWithAct(page({ itemCount: 0, query: query({ isError: true, refetch }) }));

		expect(view.container.textContent).toContain('Words could not be loaded.');
		expect(view.container.textContent).not.toContain('table:');
		expect(view.container.textContent).not.toContain('Showing');

		await view.flush(() => requireElement<HTMLButtonElement>(view.container, 'button').click());

		expect(refetch).toHaveBeenCalledTimes(1);
		await view.unmount();
	});

	it('keeps the loaded rows when a background refetch fails', () => {
		const html = renderToStaticMarkup(page({ query: query({ isError: true }) }));

		expect(html).not.toContain('could not be loaded');
		expect(html).toContain('table:');
		expect(html).toContain('Load more');
	});

	it('asks for the next page from Load more', async () => {
		const fetchNextPage = vi.fn();
		const view = await renderWithAct(page({ query: query({ fetchNextPage }) }));
		const loadMore = [...view.container.querySelectorAll('button')].find((b) => b.textContent === 'Load more');

		await view.flush(() => loadMore?.click());

		expect(fetchNextPage).toHaveBeenCalledTimes(1);
		await view.unmount();
	});
});
