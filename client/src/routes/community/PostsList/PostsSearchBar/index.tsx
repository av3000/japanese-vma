import React, { FormEvent } from 'react';
import { POST_TOPIC_OPTIONS, type PostListFilterInput } from '@/api/posts/reads';
import { Button } from '@/components/shared/Button';
import { Input, Select } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import { Grid } from '@/components/shared/layout';
import styles from './PostsSearchBar.module.css';

const ALL_TOPICS = '';

interface PostsSearchBarProps {
	defaults: PostListFilterInput;
	onSearch: (filters: PostListFilterInput) => void;
}

/**
 * Post-specific search controls.
 *
 * The shared `SearchBar` owns its own state and cannot be seeded from the URL, and it is also used
 * by Articles and Catalogues. Keeping a local control here follows `SearchBarSentences` and lets the
 * list route stay the single owner of filter state.
 */
const PostsSearchBar: React.FC<PostsSearchBarProps> = ({ defaults, onSearch }) => {
	const [keyword, setKeyword] = React.useState(defaults.keyword ?? '');
	const [topic, setTopic] = React.useState(defaults.topic ?? ALL_TOPICS);
	const [sort, setSort] = React.useState(defaults.sort ?? 'newest');

	const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		onSearch({ keyword, topic, sort });
	};

	return (
		<form onSubmit={handleSubmit} className={styles.form} role="search">
			<Grid columns={12} gap="sm">
				<Grid.Item span={{ base: 12, sm: 6, md: 4 }}>
					<Input
						type="text"
						placeholder="Ex.: title, text, #tag"
						aria-label="Search posts"
						name="keyword"
						value={keyword}
						onChange={(event) => setKeyword(event.target.value)}
					/>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 6, md: 4 }}>
					<Select
						name="topic"
						aria-label="Filter by topic"
						value={topic}
						onChange={(event) => setTopic(event.target.value)}
					>
						<option value={ALL_TOPICS}>All</option>
						{POST_TOPIC_OPTIONS.map((option) => (
							<option key={option.value} value={option.value}>
								{option.label}
							</option>
						))}
					</Select>
				</Grid.Item>
				<Grid.Item span={{ base: 6, sm: 3, md: 2 }}>
					<Select
						name="sort"
						aria-label="Sort posts"
						value={sort}
						onChange={(event) => setSort(event.target.value)}
					>
						<option value="newest">Newest</option>
						<option value="popular">Popular</option>
					</Select>
				</Grid.Item>
				<Grid.Item span={{ base: 6, sm: 3, md: 2 }}>
					<Button type="submit" variant="secondary-outline" isFullWidth>
						<Icon name="searchSolid" size="sm" />
						<span className={styles.submitLabel}>Search</span>
					</Button>
				</Grid.Item>
			</Grid>
		</form>
	);
};

export default PostsSearchBar;
