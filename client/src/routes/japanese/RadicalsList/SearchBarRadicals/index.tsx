import React, { useEffect, useState } from 'react';
import { KeywordSearchForm } from '@/components/features/SearchBar/KeywordSearchForm';

interface SearchBarRadicalsProps {
	defaultKeyword?: string;
	onSearch: (keyword: string) => void;
}

const SearchBarRadicals: React.FC<SearchBarRadicalsProps> = ({ defaultKeyword = '', onSearch }) => {
	const [keyword, setKeyword] = useState<string>(defaultKeyword);

	useEffect(() => setKeyword(defaultKeyword), [defaultKeyword]);

	return (
		<KeywordSearchForm
			id="radical-keyword"
			label="Keyword:"
			value={keyword}
			onChange={setKeyword}
			onSubmit={() => onSearch(keyword.trim())}
		/>
	);
};

export default SearchBarRadicals;
