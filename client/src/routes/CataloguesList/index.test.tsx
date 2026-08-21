import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CataloguesListPage, { mapSearchFiltersToCatalogueParams } from './index';

let queryState: Record<string, unknown>;

vi.mock('@/api/catalogues/hooks/useInfiniteCatalogues', () => ({
	useInfiniteCatalogues: () => queryState,
}));

vi.mock('@/components/features/SearchBar', () => ({
	default: () => <div>Catalogue search</div>,
}));

vi.mock('@/components/features/catalogues/CatalogueCard/CatalogueCard', () => ({
	CatalogueCard: ({ catalogue }: { catalogue: { title: string } }) => <article>{catalogue.title}</article>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({ children }: { children: React.ReactNode }) => <button>{children}</button>,
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

describe('catalogue filter mapping', () => {
	it('keeps the legacy list search UX while mapping to catalogue-v1 filters', () => {
		expect(
			mapSearchFiltersToCatalogueParams({
				keyword: '  tokyo  ',
				sortByWhat: 'pop',
				filterType: '7',
			}),
		).toEqual({
			search: 'tokyo',
			sort_by: 'views',
			sort_dir: 'desc',
			type: 7,
			per_page: 12,
			public_only: true,
			custom_only: true,
			include_stats_counts: true,
			include_hashtags: true,
		});
	});

	it('drops empty keywords and the legacy all-types sentinel', () => {
		expect(
			mapSearchFiltersToCatalogueParams({
				keyword: '   ',
				sortByWhat: 'new',
				filterType: '20',
			}),
		).toEqual({
			search: undefined,
			sort_by: 'created_at',
			sort_dir: 'desc',
			type: undefined,
			per_page: 12,
			public_only: true,
			custom_only: true,
			include_stats_counts: true,
			include_hashtags: true,
		});
	});
});

describe('CataloguesListPage', () => {
	beforeEach(() => {
		queryState = {
			catalogues: [],
			total: 0,
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			isPending: true,
			error: null,
			isError: false,
		};
	});

	it('uses the catalogue list skeleton inside accessible pending semantics', () => {
		const html = renderToStaticMarkup(<CataloguesListPage />);

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('role="status"');
		expect(html).toContain('Loading page.');
		expect(html).toContain('data-loading-family="list"');
		expect(html).toContain('data-testid="catalogues-list-skeleton"');
	});
});
