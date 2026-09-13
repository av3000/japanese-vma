import React from 'react';
import { KeywordSearchForm } from '@/components/features/SearchBar/KeywordSearchForm';

interface SearchBarSentencesProps {
	defaultKeyword?: string;
	onSearch: (keyword: string) => void;
}

const SearchBarSentences: React.FC<SearchBarSentencesProps> = ({ defaultKeyword = '', onSearch }) => {
	const [keyword, setKeyword] = React.useState<string>(defaultKeyword);

	return (
		<KeywordSearchForm
			id="sentences-search-keyword"
			label="Japanese Keyword:"
			value={keyword}
			onChange={setKeyword}
			onSubmit={() => onSearch(keyword.trim())}
		/>
	);
};

export default SearchBarSentences;
