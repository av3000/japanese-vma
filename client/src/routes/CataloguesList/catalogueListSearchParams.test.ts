import { describe, expect, it } from 'vitest';
import { DEFAULT_CATALOGUE_SEARCH_FILTERS } from '@/components/features/catalogues/CatalogueFilters';
import { parseCatalogueListSearchParams, serializeCatalogueListFilters } from './catalogueListSearchParams';
import { mapSearchFiltersToCatalogueParams } from './index';

const parse = (query: string) => parseCatalogueListSearchParams(new URLSearchParams(query));

describe('parseCatalogueListSearchParams', () => {
	it('reads an empty URL as the default filters', () => {
		expect(parse('')).toEqual(DEFAULT_CATALOGUE_SEARCH_FILTERS);
	});

	it('reads keyword, type and sort', () => {
		expect(parse('q=%20tokyo%20&type=7&sort=pop')).toEqual({
			keyword: 'tokyo',
			filterType: '7',
			sortByWhat: 'pop',
		});
	});

	it('drops a type that is not a custom catalogue type, and an unknown sort', () => {
		expect(parse('type=20&sort=random')).toEqual(DEFAULT_CATALOGUE_SEARCH_FILTERS);
		expect(parse('type=2')).toEqual(DEFAULT_CATALOGUE_SEARCH_FILTERS);
		expect(parse('type=abc')).toEqual(DEFAULT_CATALOGUE_SEARCH_FILTERS);
	});
});

describe('serializeCatalogueListFilters', () => {
	it('gives the default filters a clean URL', () => {
		expect(serializeCatalogueListFilters(DEFAULT_CATALOGUE_SEARCH_FILTERS).toString()).toBe('');
		expect(serializeCatalogueListFilters({ ...DEFAULT_CATALOGUE_SEARCH_FILTERS, keyword: '   ' }).toString()).toBe(
			'',
		);
	});

	it('writes keyword, type and sort, trimmed', () => {
		expect(
			serializeCatalogueListFilters({ keyword: '  tokyo ', filterType: '7', sortByWhat: 'pop' }).toString(),
		).toBe('q=tokyo&type=7&sort=pop');
	});

	it('round-trips', () => {
		const filters = { keyword: '台所', filterType: '9', sortByWhat: 'pop' };

		expect(parseCatalogueListSearchParams(serializeCatalogueListFilters(filters))).toEqual(filters);
	});

	/** The URL move must not change the request: a filter state maps to the same params through the URL. */
	it('maps to the same request params through the URL as directly', () => {
		const filters = { keyword: '  tokyo  ', filterType: '7', sortByWhat: 'pop' };
		const throughUrl = parseCatalogueListSearchParams(serializeCatalogueListFilters(filters));

		expect(mapSearchFiltersToCatalogueParams(throughUrl)).toEqual(mapSearchFiltersToCatalogueParams(filters));
		expect(mapSearchFiltersToCatalogueParams(parse(''))).toEqual(mapSearchFiltersToCatalogueParams({}));
	});
});
