import {
	DEFAULT_CATALOGUE_SEARCH_FILTERS,
	type CatalogueSearchFilters,
} from '@/components/features/catalogues/CatalogueFilters';
import { CATALOGUE_TYPE_FILTER_ALL, isCustomCatalogueType } from '@/shared/constants/catalogues';

/**
 * URL <-> filter state for the Catalogues route (#389).
 *
 * The URL owns the applied keyword, type and sort, so refresh, back/forward and shared links
 * reproduce the list. The route still maps that state through `mapSearchFiltersToCatalogueParams`,
 * so the request is the same one the component-state version sent.
 *
 * Wire names: `q` for the keyword and `sort` like the Articles route; `type` for the catalogue
 * type; `sort=pop` for popular. Defaults and unknown values are dropped, so a pristine list has a
 * clean URL and a hand-edited one cannot select a type that does not exist.
 */

const SORT_VALUES = new Set(['new', 'pop']);

export const parseCatalogueListSearchParams = (searchParams: URLSearchParams): CatalogueSearchFilters => {
	const type = searchParams.get('type') ?? '';
	const sort = searchParams.get('sort') ?? '';

	return {
		keyword: searchParams.get('q')?.trim() ?? '',
		filterType: isCustomCatalogueType(Number(type)) ? type : CATALOGUE_TYPE_FILTER_ALL,
		sortByWhat: SORT_VALUES.has(sort) ? sort : DEFAULT_CATALOGUE_SEARCH_FILTERS.sortByWhat,
	};
};

export const serializeCatalogueListFilters = (filters: CatalogueSearchFilters): URLSearchParams => {
	const params = new URLSearchParams();
	const keyword = filters.keyword.trim();

	if (keyword !== '') {
		params.set('q', keyword);
	}

	if (isCustomCatalogueType(Number(filters.filterType))) {
		params.set('type', filters.filterType);
	}

	if (filters.sortByWhat !== DEFAULT_CATALOGUE_SEARCH_FILTERS.sortByWhat && SORT_VALUES.has(filters.sortByWhat)) {
		params.set('sort', filters.sortByWhat);
	}

	return params;
};
