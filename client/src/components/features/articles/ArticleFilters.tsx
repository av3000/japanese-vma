import React, { useEffect, useState } from 'react';
import type { ArticleFacetResource } from '@/api/generated/model/articleFacetResource';
import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Input, Label, Select } from '@/components/shared/FormControls';
import { Cluster, Grid, Stack } from '@/components/shared/layout';
import {
	DEFAULT_SORT,
	JLPT_LEVELS,
	MIN_SEARCH_LENGTH,
	SORT_OPTIONS,
	type ArticleListFilterState,
} from '@/routes/ArticlesList/articleListSearchParams';
import styles from './ArticleFilters.module.css';

/**
 * Articles-specific filter controls.
 *
 * Deliberately not the shared SearchBar: that component still serves Posts and
 * Lists, and its control set no longer matches the v1 Article contract. Changing it
 * to fit Articles would have meant changing those routes as collateral.
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

	const handleSubmit = (event: React.FormEvent) => {
		event.preventDefault();

		if (searchTooShort) {
			return;
		}

		onSearch(draftSearch.trim());
	};

	const hasActiveFilters =
		state.q !== '' || state.jlptLevels.length > 0 || state.hashtagIds.length > 0 || state.sort !== DEFAULT_SORT;

	return (
		<Stack gap="md">
			<Grid as="form" columns={12} gap="xs" align="start" onSubmit={handleSubmit} role="search">
				<Grid.Item span={{ base: 12, sm: 6 }}>
					<Field>
						<Label className="u-hide-visually" htmlFor="article-search">
							Search articles
						</Label>
						<Input
							id="article-search"
							type="search"
							placeholder="Search article titles"
							value={draftSearch}
							onChange={(event) => setDraftSearch(event.target.value)}
						/>
						{searchTooShort && (
							<FieldMessage tone="hint">Enter at least {MIN_SEARCH_LENGTH} characters.</FieldMessage>
						)}
					</Field>
				</Grid.Item>

				<Grid.Item span={{ base: 12, sm: 4 }}>
					<Field>
						<Label className="u-hide-visually" htmlFor="article-sort">
							Sort articles
						</Label>
						<Select
							id="article-sort"
							value={state.sort}
							onChange={(event) => onSortChange(event.target.value as ArticleIndexSort)}
						>
							{SORT_OPTIONS.map((option) => (
								<option key={option.value} value={option.value}>
									{option.label}
								</option>
							))}
						</Select>
					</Field>
				</Grid.Item>

				<Grid.Item span={{ base: 12, sm: 2 }}>
					<Cluster gap="xs">
						<Button type="submit" variant="primary" disabled={searchTooShort}>
							Search
						</Button>
						{hasActiveFilters && (
							<Button type="button" variant="secondary-outline" onClick={onReset}>
								Reset
							</Button>
						)}
					</Cluster>
				</Grid.Item>
			</Grid>

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

			{hashtagFacet && hashtagFacet.values.length > 0 && (
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
			)}
		</Stack>
	);
};

export default ArticleFilters;
