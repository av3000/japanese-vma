import React, { useEffect, useState } from 'react';
import type { ArticleFacetResource } from '@/api/generated/model/articleFacetResource';
import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';
import { Button } from '@/components/shared/Button';
import { FilterBar } from '@/components/shared/FilterBar';
import { Cluster } from '@/components/shared/layout';
import {
	DEFAULT_SORT,
	JLPT_LEVELS,
	MIN_SEARCH_LENGTH,
	SORT_OPTIONS,
	type ArticleListFilterState,
} from '@/routes/ArticlesList/articleListSearchParams';
import styles from './ArticleFilters.module.css';

/**
 * Articles-specific composition of the shared FilterBar: search, sort, reset, and the facet
 * chips with server counts. The URL owns the state; this only maps it onto the bar's slots.
 */

type ArticleFiltersProps = {
	state: ArticleListFilterState;
	facets: ArticleFacetResource[];
	onSearch: (q: string) => void;
	onToggleJlptLevel: (level: ArticleIndexJlptLevelsItem) => void;
	onToggleHashtag: (hashtagId: number) => void;
	onSortChange: (sort: ArticleIndexSort) => void;
	onReset: () => void;
};

const findFacet = (facets: ArticleFacetResource[], key: string) => facets.find((facet) => facet.key === key);

const ArticleFilters: React.FC<ArticleFiltersProps> = ({
	state,
	facets,
	onSearch,
	onToggleJlptLevel,
	onToggleHashtag,
	onSortChange,
	onReset,
}) => {
	const [draftSearch, setDraftSearch] = useState(state.q);

	// The URL is the source of truth, so a back/forward navigation or a shared link
	// has to win over whatever is sitting in the input.
	useEffect(() => {
		setDraftSearch(state.q);
	}, [state.q]);

	const jlptFacet = findFacet(facets, 'jlpt_levels');
	const hashtagFacet = findFacet(facets, 'hashtag_ids');

	const searchTooShort = draftSearch.trim().length === 1;

	const handleSubmit = () => {
		if (searchTooShort) {
			return;
		}

		onSearch(draftSearch.trim());
	};

	const hasActiveFilters =
		state.q !== '' || state.jlptLevels.length > 0 || state.hashtagIds.length > 0 || state.sort !== DEFAULT_SORT;

	return (
		<FilterBar onSubmit={handleSubmit} label="Article filters">
			<FilterBar.Search
				id="article-search"
				label="Search articles"
				placeholder="Search article titles"
				value={draftSearch}
				onChange={setDraftSearch}
				hint={searchTooShort ? `Enter at least ${MIN_SEARCH_LENGTH} characters.` : undefined}
				submitDisabled={searchTooShort}
			/>

			<FilterBar.Sort
				id="article-sort"
				label="Sort articles"
				value={state.sort}
				options={SORT_OPTIONS}
				onChange={onSortChange}
			/>

			<FilterBar.Reset active={hasActiveFilters} onClick={onReset} />

			<FilterBar.Filters fullWidth>
				<fieldset>
					<legend className={styles.legend}>{jlptFacet?.label ?? 'JLPT level'}</legend>
					<Cluster gap="xs">
						{JLPT_LEVELS.map((level) => {
							// Counts come from the server when facets were requested; the control
							// still works without them so the list is usable either way.
							const facetValue = jlptFacet?.values.find((value) => value.key === level);
							const selected = state.jlptLevels.includes(level);

							return (
								<Button
									key={level}
									type="button"
									variant={selected ? 'primary' : 'secondary-outline'}
									aria-pressed={selected}
									onClick={() => onToggleJlptLevel(level)}
								>
									{facetValue?.label ?? level.toUpperCase()}
									{facetValue ? ` (${facetValue.count})` : ''}
								</Button>
							);
						})}
					</Cluster>
				</fieldset>
			</FilterBar.Filters>

			{hashtagFacet && hashtagFacet.values.length > 0 && (
				<FilterBar.Filters fullWidth>
					<fieldset>
						<legend className={styles.legend}>{hashtagFacet.label}</legend>
						<Cluster gap="xs">
							{hashtagFacet.values.map((value) => (
								<Button
									key={value.key}
									type="button"
									variant={value.selected ? 'primary' : 'secondary-outline'}
									aria-pressed={value.selected}
									onClick={() => onToggleHashtag(Number(value.key))}
								>
									#{value.label} ({value.count})
								</Button>
							))}
						</Cluster>
					</fieldset>
				</FilterBar.Filters>
			)}
		</FilterBar>
	);
};

export default ArticleFilters;
