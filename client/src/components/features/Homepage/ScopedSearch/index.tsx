import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Input, Label } from '@/components/shared/FormControls';
import styles from './ScopedSearch.module.css';
import {
	DEFAULT_SEARCH_SCOPE,
	findSearchScope,
	SEARCH_SCOPES,
	scopedSearchUrl,
	type SearchScope,
} from './scopedSearchUrl';

export { DEFAULT_SEARCH_SCOPE, SEARCH_SCOPES, scopedSearchUrl, type SearchScope } from './scopedSearchUrl';

export interface ScopedSearchProps {
	/** Scope selected on first render. Not persisted; defaults to Articles. */
	defaultScope?: SearchScope;
	className?: string;
}

/**
 * Landing-page search: pick a scope, type a keyword, land on that scope's URL-driven list.
 * No typeahead and no request of its own; the list route does the searching.
 */
export const ScopedSearch: React.FC<ScopedSearchProps> = ({ defaultScope = DEFAULT_SEARCH_SCOPE, className }) => {
	const navigate = useNavigate();
	const [scope, setScope] = React.useState<SearchScope>(defaultScope);
	const [keyword, setKeyword] = React.useState('');
	const id = React.useId();
	const inputId = `${id}-keyword`;
	const current = findSearchScope(scope);

	const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		navigate(scopedSearchUrl(scope, keyword));
	};

	return (
		<form role="search" className={classNames(styles.form, className)} onSubmit={handleSubmit}>
			<fieldset className={styles.scopes}>
				<legend className={styles.visuallyHidden}>Search in</legend>
				{SEARCH_SCOPES.map((option) => (
					<label key={option.value} className={styles.scope}>
						<input
							type="radio"
							name={`${id}-scope`}
							value={option.value}
							checked={scope === option.value}
							onChange={() => setScope(option.value)}
							className={styles.scopeInput}
						/>
						<span className={styles.scopeLabel}>{option.label}</span>
					</label>
				))}
			</fieldset>

			<div className={styles.query}>
				<Label htmlFor={inputId} className={styles.visuallyHidden}>
					{current.placeholder}
				</Label>
				<Input
					id={inputId}
					type="search"
					name="keyword"
					autoComplete="off"
					placeholder={current.placeholder}
					value={keyword}
					onChange={(event) => setKeyword(event.target.value)}
				/>
			</div>

			<Button type="submit" variant="primary" className={styles.submit}>
				Search
			</Button>
		</form>
	);
};
