import React, { useEffect, useState } from 'react';
import { PostSort } from '@/api/generated/model/postSort';
import { POST_TOPIC_OPTIONS, type PostListFilterInput, type PostListFilters } from '@/api/posts/reads';
import { FilterBar, type FilterBarOption } from '@/components/shared/FilterBar';

const ALL_TOPICS = '';

const TOPIC_OPTIONS: ReadonlyArray<FilterBarOption<string>> = [
	{ value: ALL_TOPICS, label: 'All topics' },
	...POST_TOPIC_OPTIONS.map((option) => ({ value: String(option.value), label: option.label })),
];

const SORT_OPTIONS: ReadonlyArray<FilterBarOption<PostSort>> = [
	{ value: PostSort.newest, label: 'Newest' },
	{ value: PostSort.popular, label: 'Most popular' },
];

/** The URL filters as the inputs the list writes back, so a change keeps every other filter. */
export const toFilterInput = (filters: PostListFilters): PostListFilterInput => ({
	keyword: filters.keyword ?? '',
	hashtag: filters.hashtag ?? '',
	topic: filters.topic ? String(filters.topic) : ALL_TOPICS,
	sort: filters.sort ?? PostSort.newest,
});

export const hasActivePostFilters = (filters: PostListFilters): boolean =>
	Boolean(filters.keyword || filters.hashtag || filters.topic || (filters.sort && filters.sort !== PostSort.newest));

interface PostFiltersProps {
	filters: PostListFilters;
	onChange: (next: PostListFilterInput) => void;
	onReset: () => void;
}

/**
 * Community composition of the shared FilterBar: search, topic, sort and reset. The URL owns the
 * state; topic and sort apply on change, the search applies on submit.
 */
const PostFilters: React.FC<PostFiltersProps> = ({ filters, onChange, onReset }) => {
	const current = toFilterInput(filters);
	const [draftKeyword, setDraftKeyword] = useState(current.keyword ?? '');

	// The URL is the source of truth, so back/forward or a shared link wins over the typed draft.
	useEffect(() => {
		setDraftKeyword(filters.keyword ?? '');
	}, [filters.keyword]);

	return (
		<FilterBar onSubmit={() => onChange({ ...current, keyword: draftKeyword })} label="Post filters">
			<FilterBar.Search
				id="post-search"
				label="Search posts"
				placeholder="Search post titles and text"
				value={draftKeyword}
				onChange={setDraftKeyword}
			/>

			<FilterBar.Select
				id="post-topic"
				label="Filter by topic"
				value={current.topic ?? ALL_TOPICS}
				options={TOPIC_OPTIONS}
				onChange={(topic) => onChange({ ...current, topic })}
			/>

			<FilterBar.Sort
				id="post-sort"
				label="Sort posts"
				value={filters.sort ?? PostSort.newest}
				options={SORT_OPTIONS}
				onChange={(sort) => onChange({ ...current, sort })}
			/>

			<FilterBar.Reset active={hasActivePostFilters(filters)} onClick={onReset} />
		</FilterBar>
	);
};

export default PostFilters;
