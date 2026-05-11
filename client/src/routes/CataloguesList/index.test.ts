import { describe, expect, it } from 'vitest';
import { mapSearchFiltersToCatalogueParams } from './index';

describe('mapSearchFiltersToCatalogueParams', () => {
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
