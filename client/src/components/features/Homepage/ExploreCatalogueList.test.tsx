import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { useCatalogueIndex } from '@/api/generated/catalogue/catalogue';
import { HOMEPAGE_CATALOGUE_FILTERS } from './ExploreCatalogueList';
import ExploreCatalogueList from './ExploreCatalogueList';

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@/api/generated/catalogue/catalogue', () => ({
	useCatalogueIndex: vi.fn(),
}));

describe('HOMEPAGE_CATALOGUE_FILTERS', () => {
	it('keeps the homepage catalogue section on the small public custom-list query', () => {
		expect(HOMEPAGE_CATALOGUE_FILTERS).toEqual({
			per_page: 3,
			public_only: true,
			custom_only: true,
			include_stats_counts: true,
			include_hashtags: true,
		});
	});

	it('renders the section header and four skeleton cards while catalogues are pending', () => {
		vi.mocked(useCatalogueIndex).mockReturnValue({
			data: undefined,
			isPending: true,
		} as ReturnType<typeof useCatalogueIndex>);

		const html = renderToStaticMarkup(<ExploreCatalogueList />);
		const skeletonCount = html.match(/data-testid="catalogue-card-skeleton"/g)?.length ?? 0;

		expect(html).toContain('Latest Catalogues');
		expect(skeletonCount).toBe(4);
		expect(html).not.toContain('spinner.gif');
		expect(html).not.toContain('spinner loading');
	});
});
