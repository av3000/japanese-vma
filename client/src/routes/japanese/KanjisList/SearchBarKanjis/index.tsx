import React, { ChangeEvent, FormEvent, useEffect, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Field, Input, Label, Select } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import { Grid } from '@/components/shared/layout';
import styles from './SearchBarKanjis.module.css';

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
		<form onSubmit={handleSubmit} className={styles.form} role="search">
			<Grid columns={12} gap="sm" align="end">
				<Grid.Item span={{ base: 12, sm: 5 }}>
					<Field>
						<Label htmlFor="kanji-keyword">Keyword:</Label>
						<Input
							id="kanji-keyword"
							type="text"
							size="sm"
							placeholder="Search"
							name="keyword"
							value={keyword}
							onChange={handleKeywordChange}
						/>
					</Field>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 4 }}>
					<Field>
						<Label htmlFor="kanji-jlpt">JLPT:</Label>
						<Select id="kanji-jlpt" name="jlpt" size="sm" value={jlpt} onChange={handleJlptChange}>
							<option value="">All</option>
							<option value="1">N1</option>
							<option value="2">N2</option>
							<option value="3">N3</option>
							<option value="4">N4</option>
							<option value="5">N5</option>
							<option value="-">Uncommon</option>
						</Select>
					</Field>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 3 }}>
					<Button type="submit" variant="outline" size="sm" isFullWidth>
						<Icon name="searchSolid" size="sm" />
						<span className={styles.submitLabel}>Search</span>
					</Button>
				</Grid.Item>
			</Grid>
		</form>
	);
};

export default SearchBarKanjis;
