import React, { ChangeEvent, useEffect } from 'react';
import { Input, Select } from '@/components/shared/FormControls';
import { Grid } from '@/components/shared/layout';

export interface SearchFilters {
	keyword: string;
	sortByWhat: string;
	filterType: number | string;
}

interface SearchbarProps {
	filterResults: (data: SearchFilters) => void;
	searchType?: 'articles' | 'lists' | string;
}

const Searchbar: React.FC<SearchbarProps> = ({ filterResults, searchType }) => {
	const [filters, setFilters] = React.useState<SearchFilters>({
		keyword: '',
		sortByWhat: 'new',
		filterType: 20,
	});

	useEffect(() => {
		const timeoutId = window.setTimeout(() => {
			filterResults(filters);
		}, 300);

		return () => window.clearTimeout(timeoutId);
	}, [filterResults, filters]);

	const handleChange = (e: ChangeEvent<HTMLSelectElement | HTMLInputElement>) => {
		const { name, value } = e.target;
		setFilters((prevFilters) => ({
			...prevFilters,
			[name]: value,
		}));
	};

	const isArticleSearch = searchType === 'articles';

	return (
		<form noValidate role="search">
			<Grid columns={12} gap="sm">
				<Grid.Item span={isArticleSearch ? 12 : { base: 12, sm: 6, md: 4 }}>
					<Input
						onChange={handleChange}
						size="sm"
						name="keyword"
						type="text"
						placeholder="Ex.: title, text, #tag"
						value={filters.keyword}
						aria-label="Search"
					/>
				</Grid.Item>
				{searchType === 'lists' && (
					<>
						<Grid.Item span={{ base: 12, sm: 4, md: 4 }}>
							<Select
								name="filterType"
								aria-label="Filter"
								value={filters.filterType}
								size="sm"
								onChange={handleChange}
							>
								<option value="20">All</option>
								<option value="5">Radicals</option>
								<option value="6">Kanjis</option>
								<option value="7">Words</option>
								<option value="8">Sentences</option>
								<option value="9">Articles</option>
							</Select>
						</Grid.Item>
						<Grid.Item span={{ base: 12, sm: 2, md: 4 }}>
							<Select
								name="sortByWhat"
								aria-label="Sort by"
								value={filters.sortByWhat}
								size="sm"
								onChange={handleChange}
							>
								<option value="new">Newest</option>
								<option value="pop">Popular</option>
							</Select>
						</Grid.Item>
					</>
				)}
			</Grid>
		</form>
	);
};

export default Searchbar;
