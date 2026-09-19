import React, { useEffect, useState } from 'react';
import { KeywordSearchForm } from '@/components/features/SearchBar/KeywordSearchForm';

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

	return (
		<KeywordSearchForm
			id="words-search-keyword"
			label="Japanese Keyword:"
			value={keyword}
			onChange={setKeyword}
			onSubmit={() => onSearch({ keyword: keyword.trim() })}
		/>
	);
};

export default SearchBarWords;
