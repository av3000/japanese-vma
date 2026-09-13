import React, { FormEvent } from 'react';
import { Button } from '@/components/shared/Button';
import { Input, Select } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import { Grid } from '@/components/shared/layout';
import styles from './SearchBar.module.css';

interface SearchQuery {
	keyword: string;
	sortByWhat: string;
	filterType: string;
}

interface SearchbarProps {
	fetchQuery: (data: SearchQuery) => void;
	searchType: 'posts' | 'articles' | 'lists' | string;
}

const FILTER_OPTIONS: Record<string, Array<{ value: string; label: string }>> = {
	posts: [
		{ value: '20', label: 'All' },
		{ value: '1', label: 'Content-related' },
		{ value: '2', label: 'Off-topic' },
		{ value: '3', label: 'FAQ' },
		{ value: '4', label: 'Technical' },
		{ value: '5', label: 'Bug' },
		{ value: '6', label: 'Feedback' },
		{ value: '7', label: 'Announcement' },
	],
	articles: [
		{ value: '20', label: 'All' },
		{ value: '1', label: 'N1' },
		{ value: '2', label: 'N2' },
		{ value: '3', label: 'N3' },
		{ value: '4', label: 'N4' },
		{ value: '5', label: 'N5' },
		{ value: '6', label: 'Uncommon' },
	],
	lists: [
		{ value: '20', label: 'All' },
		{ value: '5', label: 'Radicals' },
		{ value: '6', label: 'Kanjis' },
		{ value: '7', label: 'Words' },
		{ value: '8', label: 'Sentences' },
		{ value: '9', label: 'Articles' },
	],
};

const Searchbar: React.FC<SearchbarProps> = ({ fetchQuery, searchType }) => {
	const [keyword, setKeyword] = React.useState<string>('');
	const [sortByWhat, setSortByWhat] = React.useState<string>('new');
	const [filterType, setFilterType] = React.useState<string>('20');

	const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		fetchQuery({ keyword, sortByWhat, filterType });
	};

	const filterOptions = FILTER_OPTIONS[searchType];

	return (
		<form onSubmit={handleSubmit} className={styles.form} role="search">
			<Grid columns={12} gap="sm">
				<Grid.Item span={{ base: 12, sm: 6, md: 4 }}>
					<Input
						type="text"
						placeholder="Ex.: title, text, #tag"
						aria-label="Search"
						name="keyword"
						value={keyword}
						onChange={(event) => setKeyword(event.target.value)}
					/>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 6, md: 4 }}>
					{filterOptions && (
						<Select
							name="filterType"
							aria-label="Filter"
							value={filterType}
							onChange={(event) => setFilterType(event.target.value)}
						>
							{filterOptions.map((option) => (
								<option key={option.value} value={option.value}>
									{option.label}
								</option>
							))}
						</Select>
					)}
				</Grid.Item>
				<Grid.Item span={{ base: 6, sm: 3, md: 2 }}>
					<Select
						name="sortByWhat"
						aria-label="Sort by"
						value={sortByWhat}
						onChange={(event) => setSortByWhat(event.target.value)}
					>
						<option value="new">Newest</option>
						<option value="pop">Popular</option>
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

export default Searchbar;
