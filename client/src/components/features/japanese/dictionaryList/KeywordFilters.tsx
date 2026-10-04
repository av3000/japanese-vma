import * as React from 'react';
import { FilterBar } from '@/components/shared/FilterBar';

interface KeywordFiltersProps {
	/** Names the search landmark, e.g. "Word filters". */
	label: string;
	/** Visually hidden label of the input, e.g. "Search words by keyword". */
	searchLabel: string;
	placeholder: string;
	/** The applied keyword from the URL. Re-key the component when it changes. */
	defaultKeyword: string;
	onSearch: (keyword: string) => void;
}

/**
 * Keyword-only composition of the shared FilterBar, for the Words, Sentences and Radicals lists.
 * The URL owns the applied keyword; the typed draft starts from it and is applied, trimmed, on
 * Enter or the button.
 */
export const KeywordFilters: React.FC<KeywordFiltersProps> = ({
	label,
	searchLabel,
	placeholder,
	defaultKeyword,
	onSearch,
}) => {
	const [keyword, setKeyword] = React.useState(defaultKeyword);

	return (
		<FilterBar onSubmit={() => onSearch(keyword.trim())} label={label}>
			<FilterBar.Search
				label={searchLabel}
				placeholder={placeholder}
				name="keyword"
				value={keyword}
				onChange={setKeyword}
			/>
		</FilterBar>
	);
};

export default KeywordFilters;
