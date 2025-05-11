import React, { FormEvent } from 'react';
import { Col, Form, Row } from 'react-bootstrap';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface SearchQuery {
	keyword: string;
	sortByWhat: string;
	filterType: string;
}

interface SearchbarProps {
	fetchQuery: (data: SearchQuery) => void;
	searchType: 'posts' | 'articles' | 'lists' | string;
}

const Searchbar: React.FC<SearchbarProps> = ({ fetchQuery, searchType }) => {
	const [keyword, setKeyword] = React.useState<string>('');
	const [sortByWhat, setSortByWhat] = React.useState<string>('new');
	const [filterType, setFilterType] = React.useState<string>('20');

	const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		const data: SearchQuery = {
			keyword,
			sortByWhat,
			filterType,
		};
		fetchQuery(data);
	};

	const handleKeywordChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		setKeyword(e.target.value);
	};

	const handleSortChange = (e: React.ChangeEvent<HTMLElement>) => {
		const target = e.target as HTMLSelectElement;
		setSortByWhat(target.value);
	};

	const handleFilterChange = (e: React.ChangeEvent<HTMLElement>) => {
		const target = e.target as HTMLSelectElement;
		setFilterType(target.value);
	};

	return (
		<Form onSubmit={handleSubmit} className="u-container">
			<Row>
				<Col lg={4} md={6} sm={12} className="mt-3">
					<Form.Control
						type="text"
						placeholder="Ex.: title, text, #tag"
						aria-label="Search"
						name="keyword"
						value={keyword}
						onChange={handleKeywordChange}
					/>
				</Col>
				<Col lg={4} md={4} sm={12} className="mt-3">
					{searchType === 'posts' && (
						<Form.Control as="select" name="filterType" value={filterType} onChange={handleFilterChange}>
							<option value="20">All</option>
							<option value="1">Content-related</option>
							<option value="2">Off-topic</option>
							<option value="3">FAQ</option>
							<option value="4">Technical</option>
							<option value="5">Bug</option>
							<option value="6">Feedback</option>
							<option value="7">Announcement</option>
						</Form.Control>
					)}
					{searchType === 'articles' && (
						<Form.Control as="select" name="filterType" value={filterType} onChange={handleFilterChange}>
							<option value="20">All</option>
							<option value="1">N1</option>
							<option value="2">N2</option>
							<option value="3">N3</option>
							<option value="4">N4</option>
							<option value="5">N5</option>
							<option value="6">Uncommon</option>
						</Form.Control>
					)}
					{searchType === 'lists' && (
						<Form.Control as="select" name="filterType" value={filterType} onChange={handleFilterChange}>
							<option value="20">All</option>
							<option value="5">Radicals</option>
							<option value="6">Kanjis</option>
							<option value="7">Words</option>
							<option value="8">Sentences</option>
							<option value="9">Articles</option>
						</Form.Control>
					)}
				</Col>
				<Col lg={2} md={2} sm={4} className="mt-3">
					<Form.Control as="select" name="sortByWhat" value={sortByWhat} onChange={handleSortChange}>
						<option value="new">Newest</option>
						<option value="pop">Popular</option>
					</Form.Control>
				</Col>
				<Col lg={2} className="mt-3">
					<Button type="submit" variant="secondary-outline" aria-hidden="true">
						<Icon name="searchSolid" size="sm" />
						<span className="ml-2">Search</span>
					</Button>
				</Col>
			</Row>
		</Form>
	);
};

export default Searchbar;
