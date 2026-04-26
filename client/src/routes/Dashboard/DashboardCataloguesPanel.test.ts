import { describe, expect, it } from 'vitest';
import { mapDashboardSearchFiltersToCatalogueFilters } from './DashboardCataloguesPanel';

describe('mapDashboardSearchFiltersToCatalogueFilters', () => {
	it('maps the dashboard search bar filters onto catalogue-v1 filters', () => {
		expect(
			mapDashboardSearchFiltersToCatalogueFilters({
				keyword: '  tokyo  ',
				sortByWhat: 'pop',
				filterType: '7',
			}),
		).toEqual({
			search: 'tokyo',
			sort_by: 'views',
			sort_dir: 'desc',
			type: 7,
		});
	});

	it('drops empty keywords and the all-types sentinel', () => {
		expect(
			mapDashboardSearchFiltersToCatalogueFilters({
				keyword: '   ',
				sortByWhat: 'new',
				filterType: '20',
			}),
		).toEqual({
			search: undefined,
			sort_by: 'created_at',
			sort_dir: 'desc',
			type: undefined,
		});
	});
});
