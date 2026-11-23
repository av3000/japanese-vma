import * as React from 'react';
import { useLocation } from 'react-router-dom';
import classNames from 'classnames';
import { Icon } from '@/components/shared/Icon';
import styles from './HeaderSearch.module.css';
import { optionId, SearchBody } from './SearchBody';
import { useHeaderSearch } from './useHeaderSearch';

export const SEARCH_PLACEHOLDER = 'Search articles, kanji, words…';

export interface HeaderSearchPanelProps {
	/** Lets the Header's "/" shortcut focus the input. */
	inputRef?: React.RefObject<HTMLInputElement | null>;
	className?: string;
}

/**
 * Desktop Header search: a combobox input with a panel anchored under it. The panel opens on
 * focus and stays open while focus is anywhere in the form; Escape, a search, or focus leaving the
 * form closes it. ↑/↓ move the highlighted row and Enter runs it.
 */
export const HeaderSearchPanel: React.FC<HeaderSearchPanelProps> = ({ inputRef, className }) => {
	const baseId = React.useId();
	const inputId = `${baseId}-input`;
	const panelId = `${baseId}-panel`;
	const listboxId = `${baseId}-listbox`;
	const fallbackRef = React.useRef<HTMLInputElement>(null);
	const ref = inputRef ?? fallbackRef;
	const formRef = React.useRef<HTMLFormElement>(null);
	const [isOpen, setIsOpen] = React.useState(false);
	const { pathname } = useLocation();

	const search = useHeaderSearch(() => {
		setIsOpen(false);
		ref.current?.blur();
	});
	const { hasQuery, resolution, activeIndex } = search;

	React.useEffect(() => {
		setIsOpen(false);
	}, [pathname]);

	const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
		if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
			event.preventDefault();
			if (!isOpen) setIsOpen(true);
			else search.moveActive(event.key === 'ArrowDown' ? 1 : -1);
		} else if (event.key === 'Escape' && isOpen) {
			// Also stops a search input from clearing itself on Escape.
			event.preventDefault();
			setIsOpen(false);
		}
	};

	const handleBlur = (event: React.FocusEvent<HTMLFormElement>) => {
		if (!formRef.current?.contains(event.relatedTarget as Node | null)) setIsOpen(false);
	};

	const activeOption = isOpen && hasQuery ? optionId(baseId, resolution.order[activeIndex]) : undefined;

	return (
		<form
			ref={formRef}
			role="search"
			className={classNames(styles.form, className)}
			autoComplete="off"
			onSubmit={(event) => {
				event.preventDefault();
				search.run();
			}}
			onBlur={handleBlur}
		>
			<label htmlFor={inputId} className={styles.visuallyHidden}>
				Search JPLearning
			</label>
			<div className={styles.field}>
				<Icon name="searchSolid" size="sm" className={styles.fieldIcon} />
				<input
					ref={ref}
					id={inputId}
					type="search"
					role="combobox"
					className={styles.input}
					placeholder={SEARCH_PLACEHOLDER}
					enterKeyHint="search"
					aria-autocomplete="none"
					aria-expanded={isOpen}
					aria-controls={isOpen ? (hasQuery ? listboxId : panelId) : undefined}
					aria-activedescendant={activeOption}
					aria-keyshortcuts="/"
					value={search.query}
					onChange={(event) => {
						search.setQuery(event.target.value);
						setIsOpen(true);
					}}
					onFocus={() => setIsOpen(true)}
					onKeyDown={handleKeyDown}
				/>
				<kbd className={styles.kbd} aria-hidden="true">
					/
				</kbd>
			</div>

			{isOpen && (
				// Keeps focus in the input while a chip or row is clicked, so typing carries on. Not an
				// interaction of its own: the chips are buttons and the rows are driven by the combobox.
				// eslint-disable-next-line jsx-a11y/no-static-element-interactions
				<div id={panelId} className={styles.panel} onMouseDown={(event) => event.preventDefault()}>
					<SearchBody search={search} baseId={baseId} mode="panel" />
					<div className={styles.footer} aria-hidden="true">
						<span>
							<kbd className={styles.kbd}>↑</kbd> <kbd className={styles.kbd}>↓</kbd> choose
						</span>
						<span>
							<kbd className={styles.kbd}>↵</kbd> search
						</span>
						<span>
							<kbd className={styles.kbd}>esc</kbd> close
						</span>
					</div>
				</div>
			)}
		</form>
	);
};
