import React, { useEffect, useState } from 'react';
import type { ArticleFacetResource } from '@/api/generated/model/articleFacetResource';
import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';
import { Button } from '@/components/shared/Button';
import { JLPT_LEVELS, SORT_OPTIONS, type ArticleListFilterState } from '@/routes/ArticlesList/articleListSearchParams';

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

const MIN_SEARCH_LENGTH = 2;

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
		state.q !== '' || state.jlptLevels.length > 0 || state.hashtagIds.length > 0 || state.sort !== '-created_at';

	return (
		<div className="mb-4">
			<form className="row g-2 align-items-center mb-3" onSubmit={handleSubmit} role="search">
				<div className="col-md-6">
					<label className="visually-hidden" htmlFor="article-search">
						Search articles
					</label>
					<input
						id="article-search"
						type="search"
						className="form-control"
						placeholder="Search article titles"
						value={draftSearch}
						onChange={(event) => setDraftSearch(event.target.value)}
					/>
					{searchTooShort && (
						<small className="text-muted">Enter at least {MIN_SEARCH_LENGTH} characters.</small>
					)}
				</div>

				<div className="col-md-4">
					<label className="visually-hidden" htmlFor="article-sort">
						Sort articles
					</label>
					<select
						id="article-sort"
						className="form-select"
						value={state.sort}
						onChange={(event) => onSortChange(event.target.value as ArticleIndexSort)}
					>
						{SORT_OPTIONS.map((option) => (
							<option key={option.value} value={option.value}>
								{option.label}
							</option>
						))}
					</select>
				</div>

				<div className="col-md-2 d-flex gap-2">
					<Button type="submit" variant="primary" disabled={searchTooShort}>
						Search
					</Button>
					{hasActiveFilters && (
						<Button type="button" variant="secondary-outline" onClick={onReset}>
							Reset
						</Button>
					)}
				</div>
			</form>

			<fieldset className="mb-3">
				<legend className="fs-6 text-muted">{jlptFacet?.label ?? 'JLPT level'}</legend>
				<div className="d-flex flex-wrap gap-2">
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
				</div>
			</fieldset>

			{hashtagFacet && hashtagFacet.values.length > 0 && (
				<fieldset>
					<legend className="fs-6 text-muted">{hashtagFacet.label}</legend>
					<div className="d-flex flex-wrap gap-2">
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
					</div>
				</fieldset>
			)}
		</div>
	);
};

export default ArticleFilters;
