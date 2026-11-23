// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueResource } from '@/api/generated/model';
import { makeCatalogue, popularCatalogues } from '../fixtures';
import { catalogueMeta, PopularCatalogues } from './index';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const refetch = vi.fn();
const hookArgs = vi.fn();
const query = vi.hoisted(() => ({ value: {} as Record<string, unknown> }));

vi.mock('@/api/generated/catalogue/catalogue', () => ({
	useCatalogueIndex: (params: unknown) => {
		hookArgs(params);
		return query.value;
	},
}));

let container: HTMLDivElement;
let root: Root;

const render = (state: Record<string, unknown>) => {
	query.value = { refetch, data: undefined, ...state };
	act(() => {
		root.render(
			<MemoryRouter>
				<PopularCatalogues />
			</MemoryRouter>,
		);
	});
};

const loaded = (items: CatalogueResource[], total = items.length) =>
	render({ status: 'success', data: { items, pagination: { total } } });

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

describe('PopularCatalogues', () => {
	it('requests the four most viewed public custom catalogues with their counts', () => {
		render({ status: 'pending' });

		expect(hookArgs).toHaveBeenCalledWith({
			per_page: 4,
			public_only: true,
			custom_only: true,
			sort_by: 'views',
			sort_dir: 'desc',
			include_stats_counts: true,
		});
	});

	it('renders four rows with type, linked title and item/view counts', () => {
		loaded(popularCatalogues, 37);

		const rows = Array.from(container.querySelectorAll('ul > li'));
		expect(rows).toHaveLength(4);
		expect(rows.map((row) => row.querySelector('a')?.getAttribute('href'))).toEqual([
			'/catalogues/catalogue-1',
			'/catalogues/catalogue-2',
			'/catalogues/catalogue-3',
			'/catalogues/catalogue-4',
		]);
		expect(rows[0].textContent).toContain('Kanji');
		expect(rows[0].textContent).toContain('42 items · 1,532 views');
		expect(rows[1].textContent).toContain('1 item · 980 views');
		expect(rows[3].textContent).toContain('6 items');
		expect(rows[3].textContent).not.toContain('views');
		expect(container.querySelector('a[href="/catalogues"]')?.textContent).toBe('All 37');
	});

	it('falls back to the numeric type label when type_label is missing', () => {
		loaded([makeCatalogue({ type: 7, type_label: undefined as unknown as CatalogueResource['type_label'] })]);

		expect(container.querySelector('li')?.textContent).toContain('Words');
	});

	it('shows four skeleton rows while loading', () => {
		render({ status: 'pending' });

		expect(container.querySelectorAll('[data-testid="compact-list-skeleton"]')).toHaveLength(4);
	});

	it('shows one line when there are no public catalogues', () => {
		loaded([], 0);

		expect(container.textContent).toContain('No public catalogues yet');
	});

	it('shows a fixed error line and retries, never the raw error message', () => {
		render({ status: 'error', error: new Error('Internal Server Error: stack trace') });

		expect(container.textContent).toContain("Couldn't load catalogues");
		expect(container.textContent).not.toContain('stack trace');

		act(() => retryButton()?.click());
		expect(refetch).toHaveBeenCalledTimes(1);
	});
});

describe('catalogueMeta', () => {
	it('pluralises and formats counts', () => {
		expect(catalogueMeta(makeCatalogue({ items_count: 1 }))).toBe('1 item · 1,532 views');
	});

	it('leaves out views when the engagement block is missing', () => {
		expect(catalogueMeta(makeCatalogue({ items_count: 6, engagement: null }))).toBe('6 items');
	});
});
