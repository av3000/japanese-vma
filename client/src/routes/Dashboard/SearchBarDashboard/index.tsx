import React, { ChangeEvent, useEffect } from 'react';

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
		<div className="container">
			<form noValidate>
				<div className="row justify-content-end">
					<div className={isArticleSearch ? 'col-lg-12 col-md-12 col-sm-12 mt-3' : 'col-lg-4 col-md-6 col-sm-12 mt-3'}>
						<input
							onChange={handleChange}
							className="form-control form-control-sm"
							name="keyword"
							type="text"
							placeholder="Ex.: title, text, #tag"
							value={filters.keyword}
							aria-label="Search"
						/>
					</div>
					{searchType === 'lists' && (
						<>
							<div className="col-lg-4 col-md-4 col-sm-12 mt-3">
								<select
									name="filterType"
									value={filters.filterType}
									className="form-control form-control-sm"
									onChange={handleChange}
								>
									<option value="20">All</option>
									<option value="5">Radicals</option>
									<option value="6">Kanjis</option>
									<option value="7">Words</option>
									<option value="8">Sentences</option>
									<option value="9">Articles</option>
								</select>
							</div>
							<div className="col-lg-4 col-md-2 col-sm-12 mt-3">
								<select
									name="sortByWhat"
									value={filters.sortByWhat}
									className="form-control form-control-sm"
									onChange={handleChange}
								>
									<option value="new">Newest</option>
									<option value="pop">Popular</option>
								</select>
							</div>
						</>
					)}
				</div>
			</form>
		</div>
	);
};

export default Searchbar;
