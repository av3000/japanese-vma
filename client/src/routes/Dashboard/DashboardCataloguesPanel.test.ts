import { describe, expect, it } from 'vitest';
import { dashboardCatalogueFilters, mapDashboardSearchFiltersToCatalogueFilters } from './DashboardCataloguesPanel';
import { defaultDashboardViewState } from './dashboardSearchParams';

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

	it('drops a keyword below the backend minimum, which the catalogue index would reject', () => {
		expect(
			mapDashboardSearchFiltersToCatalogueFilters({ keyword: ' a ', sortByWhat: 'new', filterType: '' }).search,
		).toBe(undefined);
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

describe('dashboardCatalogueFilters', () => {
	it('asks for every list the owner has, 25 at a time, with counts and without tags', () => {
		expect(dashboardCatalogueFilters('owner-uuid', defaultDashboardViewState('lists'))).toEqual({
			owner_uid: 'owner-uuid',
			search: undefined,
			sort_by: 'created_at',
			sort_dir: 'desc',
			type: undefined,
			public_only: false,
			custom_only: false,
			per_page: 25,
			include_stats_counts: true,
			include_hashtags: false,
		});
	});
});
