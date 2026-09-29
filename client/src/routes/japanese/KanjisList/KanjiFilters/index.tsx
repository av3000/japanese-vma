import React, { useState } from 'react';
import { FilterBar } from '@/components/shared/FilterBar';

export interface KanjiSearchFilters {
	keyword: string;
	jlpt: string;
}

interface KanjiFiltersProps {
	defaultKeyword: string;
	defaultJlpt: string;
	onSearch: (filters: KanjiSearchFilters) => void;
}

const JLPT_OPTIONS = [
	{ value: '', label: 'All levels' },
	{ value: '1', label: 'N1' },
	{ value: '2', label: 'N2' },
	{ value: '3', label: 'N3' },
	{ value: '4', label: 'N4' },
	{ value: '5', label: 'N5' },
	{ value: '-', label: 'Uncommon' },
] as const;

/**
 * Kanji-specific composition of the shared FilterBar: keyword plus a JLPT level. The URL owns the
 * applied filters (`defaultKeyword`, `defaultJlpt`). Choosing a level applies at once, along with
 * whatever keyword is typed; the keyword alone waits for Enter or the button. The draft starts from
 * the applied values, so the parent re-keys this component when they change.
 */
const KanjiFilters: React.FC<KanjiFiltersProps> = ({ defaultKeyword, defaultJlpt, onSearch }) => {
	const [keyword, setKeyword] = useState(defaultKeyword);
	const [jlpt, setJlpt] = useState(defaultJlpt);

	return (
		<FilterBar onSubmit={() => onSearch({ keyword: keyword.trim(), jlpt })} label="Kanji filters">
			<FilterBar.Search
				label="Search kanji by keyword"
				placeholder="Search"
				name="keyword"
				value={keyword}
				onChange={setKeyword}
			/>
			<FilterBar.Filters>
				<FilterBar.Select
					label="JLPT level"
					name="jlpt"
					value={jlpt}
					options={JLPT_OPTIONS}
					onChange={(nextJlpt) => {
						setJlpt(nextJlpt);
						onSearch({ keyword: keyword.trim(), jlpt: nextJlpt });
					}}
				/>
			</FilterBar.Filters>
		</FilterBar>
	);
};

export default KanjiFilters;
