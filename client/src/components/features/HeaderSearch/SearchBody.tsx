import * as React from 'react';
import classNames from 'classnames';
import { Icon } from '@/components/shared/Icon';
import styles from './HeaderSearch.module.css';
import { containsJapanese, rowNote } from './detectScope';
import { findSearchScope, SCOPE_REFINES, type SearchScope } from './searchScopes';
import type { HeaderSearchState } from './useHeaderSearch';

/** Row wording around the query, per scope. */
const ROW_TEXT: Record<SearchScope, readonly [before: string, after: string]> = {
	articles: ['Articles with ', ' in the title'],
	kanji: ['Kanji for ', ''],
	words: ['Words containing ', ''],
	sentences: ['Sentences with ', ''],
	radicals: ['Radicals matching ', ''],
};

const scopeLabel = (scope: SearchScope) => findSearchScope(scope).label;

/** The query as typed, in the Japanese font stack only when it contains Japanese. */
export const QueryText: React.FC<{ text: string }> = ({ text }) => (
	<span className={styles.query} lang={containsJapanese(text) ? 'ja' : undefined}>
		{text}
	</span>
);

export const optionId = (baseId: string, scope: SearchScope) => `${baseId}-option-${scope}`;

export interface SearchBodyProps {
	search: HeaderSearchState;
	/** Prefix for the ids the combobox points at. */
	baseId: string;
	/**
	 * `panel`: the rows are a listbox driven by the input (`aria-activedescendant`, ↑/↓, Enter).
	 * `drawer`: the rows are plain buttons, because on touch every row is tapped directly.
	 */
	mode: 'panel' | 'drawer';
}

/** Scope chips, the "Search … in" rows or recent searches, and the refine chips. */
export const SearchBody: React.FC<SearchBodyProps> = ({ search, baseId, mode }) => {
	const { query, hasQuery, resolution, activeIndex, refine, recent } = search;
	const trimmed = query.trim();
	const refineDefinition = SCOPE_REFINES[resolution.scope];
	const scopesLabelId = `${baseId}-scopes-label`;
	const rowsLabelId = `${baseId}-rows-label`;
	const recentLabelId = `${baseId}-recent-label`;
	const refineLabelId = `${baseId}-refine-label`;

	const rowContent = (scope: SearchScope, index: number) => {
		const [before, after] = ROW_TEXT[scope];
		const note = rowNote(scope, index, resolution);

		return (
			<>
				<Icon name="searchSolid" size="sm" className={styles.rowIcon} />
				<span className={styles.rowText}>
					{before}
					<QueryText text={trimmed} />
					{after}
					{note && (
						<>
							{' '}
							<span className={styles.note}>· {note}</span>
						</>
					)}
				</span>
				{/* #383 adds a per-scope hit count here, before the Enter hint. */}
				<kbd className={styles.enterHint} aria-hidden="true">
					↵
				</kbd>
			</>
		);
	};

	return (
		<div className={styles.body}>
			<p id={scopesLabelId} className={styles.label}>
				Search in
			</p>
			<ul className={styles.chips} aria-labelledby={scopesLabelId}>
				{resolution.order.map((scope) => {
					const isBest = resolution.bestMatch === scope;
					return (
						<li key={scope}>
							<button
								type="button"
								className={styles.chip}
								aria-pressed={resolution.scope === scope}
								onClick={() => search.pickScope(scope)}
							>
								{isBest && <Icon name="sparkle" size="sm" />}
								{scopeLabel(scope)}
								{isBest && (
									<>
										{' '}
										<span className={styles.bestTag}>Best match</span>
									</>
								)}
							</button>
						</li>
					);
				})}
			</ul>

			{hasQuery && (
				<>
					<p id={rowsLabelId} className={styles.label}>
						Search <QueryText text={trimmed} /> in
					</p>
					{mode === 'panel' ? (
						<ul
							id={`${baseId}-listbox`}
							role="listbox"
							className={styles.rows}
							aria-labelledby={rowsLabelId}
						>
							{resolution.order.map((scope, index) => (
								// Keyboard users reach the options through the combobox input (↑/↓, Enter).
								// eslint-disable-next-line jsx-a11y/click-events-have-key-events
								<li
									key={scope}
									id={optionId(baseId, scope)}
									role="option"
									aria-selected={index === activeIndex}
									className={classNames(styles.row, index === activeIndex && styles.rowActive)}
									onClick={() => search.run(scope)}
								>
									{rowContent(scope, index)}
								</li>
							))}
						</ul>
					) : (
						<ul className={styles.rows} aria-labelledby={rowsLabelId}>
							{resolution.order.map((scope, index) => (
								<li key={scope}>
									<button
										type="button"
										className={classNames(styles.row, index === activeIndex && styles.rowActive)}
										onClick={() => search.run(scope)}
									>
										{rowContent(scope, index)}
									</button>
								</li>
							))}
						</ul>
					)}
				</>
			)}

			{!hasQuery && recent.length > 0 && (
				<>
					<p id={recentLabelId} className={styles.label}>
						Recent searches
					</p>
					<ul className={styles.rows} aria-labelledby={recentLabelId}>
						{recent.map((entry) => (
							<li key={`${entry.scope}|${entry.query}`}>
								<button type="button" className={styles.row} onClick={() => search.runRecent(entry)}>
									<Icon name="clock" size="sm" className={styles.rowIcon} />
									<span className={styles.rowText}>
										<QueryText text={entry.query} />{' '}
										<span className={styles.note}>· {scopeLabel(entry.scope)}</span>
									</span>
								</button>
							</li>
						))}
					</ul>
				</>
			)}

			{refineDefinition && (
				<>
					<p id={refineLabelId} className={styles.label}>
						Refine {scopeLabel(resolution.scope).toLowerCase()} · {refineDefinition.label}
					</p>
					<ul className={styles.chips} aria-labelledby={refineLabelId}>
						{!refineDefinition.multiple && (
							<li>
								<button
									type="button"
									className={styles.chip}
									aria-pressed={refine.length === 0}
									onClick={search.clearRefine}
								>
									Any
								</button>
							</li>
						)}
						{refineDefinition.options.map((option) => (
							<li key={option.value}>
								<button
									type="button"
									className={styles.chip}
									aria-pressed={refine.includes(option.value)}
									onClick={() => search.toggleRefine(option.value)}
								>
									{option.label}
								</button>
							</li>
						))}
					</ul>
				</>
			)}
		</div>
	);
};
