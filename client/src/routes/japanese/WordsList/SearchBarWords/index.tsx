import React, { FormEvent, useEffect, useState } from 'react';
import { Form, InputGroup } from 'react-bootstrap';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

export type WordSearchFilters = {
	keyword: string;
};

interface SearchBarWordsProps {
	defaultKeyword?: string;
	onSearch: (query: WordSearchFilters) => void;
}

const SearchBarWords: React.FC<SearchBarWordsProps> = ({ defaultKeyword = '', onSearch }) => {
	const [keyword, setKeyword] = useState(defaultKeyword);

	useEffect(() => setKeyword(defaultKeyword), [defaultKeyword]);

	const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		onSearch({ keyword: keyword.trim() });
	};

	const handleKeywordChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		setKeyword(event.target.value);
	};

	return (
		<Form onSubmit={handleSubmit} className="col-lg-12">
			<div className="justify-content-center">
				<div className="col-lg-4 col-md-5 col-sm-12 mb-2">
					<Form.Label>Japanese Keyword:</Form.Label>
					<InputGroup size="sm">
						<Form.Control
							type="text"
							placeholder="Search"
							aria-label="Search"
							name="keyword"
							value={keyword}
							onChange={handleKeywordChange}
						/>
					</InputGroup>
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
		</Form>
	);
};

export default SearchBarWords;
