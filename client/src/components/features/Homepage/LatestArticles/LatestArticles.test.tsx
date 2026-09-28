// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { latestArticles } from '../fixtures';
import { LATEST_ARTICLES_FILTERS, LatestArticles } from './index';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const refetch = vi.fn();
const hookArgs = vi.fn();
const query = vi.hoisted(() => ({ value: {} as Record<string, unknown> }));

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: (options: unknown) => {
		hookArgs(options);
		return query.value;
	},
}));

let container: HTMLDivElement;
let root: Root;

const render = (state: Record<string, unknown>) => {
	query.value = { refetch, articles: [], total: 0, ...state };
	act(() => {
		root.render(
			<MemoryRouter>
				<LatestArticles />
			</MemoryRouter>,
		);
	});
};

const retryButton = () =>
	Array.from(container.querySelectorAll('button')).find((button) => button.textContent === 'Retry');

beforeEach(() => {
	refetch.mockClear();
	hookArgs.mockClear();
	container = document.createElement('div');
	document.body.appendChild(container);
	root = createRoot(container);
});

afterEach(() => {
	act(() => root.unmount());
	container.remove();
});

describe('LatestArticles', () => {
	it('asks for four articles without facets', () => {
		render({ status: 'pending' });

		expect(hookArgs).toHaveBeenCalledWith({ filters: { per_page: 4, include_facets: false } });
		expect(LATEST_ARTICLES_FILTERS).toEqual({ per_page: 4, include_facets: false });
	});

	it('renders four rows linking to each article, with a Japanese title, the JLPT bar and a date', () => {
		render({ status: 'success', articles: latestArticles, total: 128 });

		const rows = container.querySelectorAll('ul > li');
		expect(rows).toHaveLength(4);

		const links = Array.from(container.querySelectorAll<HTMLAnchorElement>('li a'));
		expect(links.map((link) => link.getAttribute('href'))).toEqual([
			'/articles/article-1',
			'/articles/article-2',
			'/articles/article-3',
			'/articles/article-4',
		]);
		expect(links.every((link) => link.lang === 'ja')).toBe(true);
		expect(rows[0].querySelector('time')?.getAttribute('datetime')).toBe('2026-09-24T08:00:00Z');
		// The still-processing article (all counts 0) has no bar.
		expect(rows[0].querySelector('[role="img"]')).not.toBeNull();
		expect(rows[3].querySelector('[role="img"]')).toBeNull();

		const all = container.querySelector<HTMLAnchorElement>('a[href="/articles"]');
		expect(all?.textContent).toBe('All 128');
		expect(all?.getAttribute('aria-label')).toBe('All 128 articles');
	});

	it('shows four skeleton rows while loading', () => {
		render({ status: 'pending' });

		expect(container.querySelectorAll('[data-testid="compact-list-skeleton"]')).toHaveLength(4);
		expect(container.querySelector('a[href="/articles"]')?.textContent).toBe('All articles');
	});

	it('shows one line when there are no articles', () => {
		render({ status: 'success', articles: [], total: 0 });

		expect(container.textContent).toContain('No articles yet');
		expect(container.querySelector('ul')).toBeNull();
	});

	it('shows a fixed error line and retries, never the raw error message', () => {
		render({ status: 'error', error: new Error('SQLSTATE[HY000] secret detail') });

		expect(container.textContent).toContain("Couldn't load articles");
		expect(container.textContent).not.toContain('SQLSTATE');

		act(() => retryButton()?.click());
		expect(refetch).toHaveBeenCalledTimes(1);
	});
});
