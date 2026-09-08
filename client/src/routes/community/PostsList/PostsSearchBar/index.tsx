import React, { FormEvent } from 'react';
import { Col, Form, Row } from 'react-bootstrap';
import { POST_TOPIC_OPTIONS, type PostListFilterInput } from '@/api/posts/reads';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

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
		<Form onSubmit={handleSubmit} className="col-lg-12">
			<Row>
				<Col lg={4} md={6} sm={12}>
					<Form.Control
						type="text"
						placeholder="Ex.: title, text, #tag"
						aria-label="Search posts"
						name="keyword"
						value={keyword}
						onChange={(event) => setKeyword(event.target.value)}
					/>
				</Col>
				<Col lg={4} md={4} sm={12}>
					<Form.Control
						as="select"
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
					</Form.Control>
				</Col>
				<Col lg={2} md={2} sm={4}>
					<Form.Control
						as="select"
						name="sort"
						aria-label="Sort posts"
						value={sort}
						onChange={(event) => setSort(event.target.value)}
					>
						<option value="newest">Newest</option>
						<option value="popular">Popular</option>
					</Form.Control>
				</Col>
				<Col lg={2} md={3} sm={4}>
					<Button type="submit" variant="secondary-outline" isFullWidth>
						<Icon name="searchSolid" size="sm" />
						<span className="ml-2">Search</span>
					</Button>
				</Col>
			</Row>
		</Form>
	);
};

export default PostsSearchBar;
