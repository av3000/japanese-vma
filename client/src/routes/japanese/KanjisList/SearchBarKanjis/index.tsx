import React, { ChangeEvent, FormEvent, useEffect, useState } from 'react';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

export interface KanjiSearchFilters {
	keyword: string;
	jlpt: string;
}

interface SearchBarKanjisProps {
	defaultKeyword: string;
	defaultJlpt: string;
	onSearch: (filters: KanjiSearchFilters) => void;
}

const SearchBarKanjis: React.FC<SearchBarKanjisProps> = ({ defaultKeyword, defaultJlpt, onSearch }) => {
	const [keyword, setKeyword] = useState(defaultKeyword);
	const [jlpt, setJlpt] = useState(defaultJlpt);

	useEffect(() => {
		setKeyword(defaultKeyword);
		setJlpt(defaultJlpt);
	}, [defaultKeyword, defaultJlpt]);

	const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		onSearch({ keyword: keyword.trim(), jlpt });
	};

	const handleKeywordChange = (e: ChangeEvent<HTMLInputElement>) => {
		setKeyword(e.target.value);
	};

	const handleJlptChange = (e: ChangeEvent<HTMLSelectElement>) => {
		setJlpt(e.target.value);
	};

	return (
		<form onSubmit={handleSubmit} className="col-lg-12">
			<div className="row justify-content-center">
				<div className="col-lg-4 col-md-5 col-sm-12 mb-2">
					<label htmlFor="kanji-keyword">Keyword:</label>
					<div className="input-group input-group-sm">
						<input
							id="kanji-keyword"
							type="text"
							placeholder="Search"
							aria-label="Search"
							name="keyword"
							value={keyword}
							onChange={handleKeywordChange}
							className="form-control form-control-sm"
						/>
					</div>
				</div>
				<div className="col-lg-3 col-md-4 col-sm-12 mb-2">
					<label htmlFor="kanji-jlpt">JLPT:</label>
					<div className="input-group input-group-sm">
						<select
							id="kanji-jlpt"
							name="jlpt"
							value={jlpt}
							onChange={handleJlptChange}
							className="form-control form-control-sm"
						>
							<option value="">All</option>
							<option value="1">N1</option>
							<option value="2">N2</option>
							<option value="3">N3</option>
							<option value="4">N4</option>
							<option value="5">N5</option>
							<option value="-">Uncommon</option>
						</select>
					</div>
				</div>
			</div>
			<div className="row justify-content-center">
				<div className="col-lg-2 col-md-3 col-sm-4 text-center">
					<Button type="submit" variant="outline" size="md">
						<Icon name="searchSolid" size="sm" />
						<span className="ml-2">Search</span>
					</Button>
				</div>
			</div>
		</form>
	);
};

export default SearchBarKanjis;
