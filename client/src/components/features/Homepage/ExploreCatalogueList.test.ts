import { describe, expect, it } from 'vitest';
import { HOMEPAGE_CATALOGUE_FILTERS } from './ExploreCatalogueList';

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
});
