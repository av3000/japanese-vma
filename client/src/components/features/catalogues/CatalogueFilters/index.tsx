import React from 'react';
import { FilterBar } from '@/components/shared/FilterBar';
import { CATALOGUE_TYPE_FILTER_ALL, CATALOGUE_TYPE_FILTER_OPTIONS } from '@/shared/constants/catalogues';

/** What a catalogue search form edits. The page maps it onto its own request filters. */
export interface CatalogueSearchFilters {
	keyword: string;
	sortByWhat: string;
	/** A catalogue type as a select value, or `CATALOGUE_TYPE_FILTER_ALL`. */
	filterType: string;
}

export const DEFAULT_CATALOGUE_SEARCH_FILTERS: CatalogueSearchFilters = {
	keyword: '',
	sortByWhat: 'new',
	filterType: CATALOGUE_TYPE_FILTER_ALL,
};

const SORT_OPTIONS = [
	{ value: 'new', label: 'Newest' },
	{ value: 'pop', label: 'Popular' },
] as const;

interface CatalogueFiltersProps {
	value: CatalogueSearchFilters;
	onChange: (next: CatalogueSearchFilters) => void;
	onSubmit: () => void;
}

/**
 * Catalogue-specific composition of the shared FilterBar: keyword, type and sort. The page owns
 * the values, so it decides when they turn into a request (on submit, or as the user types).
 */
export const CatalogueFilters: React.FC<CatalogueFiltersProps> = ({ value, onChange, onSubmit }) => (
	<FilterBar onSubmit={onSubmit} label="Catalogue filters">
		<FilterBar.Search
			label="Search catalogues"
			placeholder="Ex.: title, text, #tag"
			value={value.keyword}
			onChange={(keyword) => onChange({ ...value, keyword })}
		/>
		<FilterBar.Filters>
			<FilterBar.Select
				label="Catalogue type"
				value={value.filterType}
				options={CATALOGUE_TYPE_FILTER_OPTIONS}
				onChange={(filterType) => onChange({ ...value, filterType })}
			/>
		</FilterBar.Filters>
		<FilterBar.Sort
			value={value.sortByWhat}
			options={SORT_OPTIONS}
			onChange={(sortByWhat) => onChange({ ...value, sortByWhat })}
		/>
	</FilterBar>
);

export default CatalogueFilters;
