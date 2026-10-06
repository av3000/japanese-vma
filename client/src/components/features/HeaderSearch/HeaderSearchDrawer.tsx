import * as React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Drawer } from '@/components/shared/Drawer';
import { Icon } from '@/components/shared/Icon';
import { useModal } from '@/hooks/useModal';
import styles from './HeaderSearch.module.css';
import { SEARCH_PLACEHOLDER } from './HeaderSearchPanel';
import { SearchBody } from './SearchBody';
import { findSearchScope } from './searchScopes';
import { useHeaderSearch } from './useHeaderSearch';

export interface HeaderSearchDrawerProps {
	/** Lets the Header's "/" shortcut open the drawer from its trigger. */
	triggerRef?: React.RefObject<HTMLButtonElement | null>;
	/** Class for the trigger button, which is the only part laid out in the Header bar. */
	className?: string;
}

/**
 * Mobile Header search: an icon button that opens a full-height search drawer. Inside, the same
 * chips, rows, refine chips and recent searches as the desktop panel, with a primary button pinned
 * at the bottom. Escape or Cancel closes it and returns focus to the button.
 */
export const HeaderSearchDrawer: React.FC<HeaderSearchDrawerProps> = ({ triggerRef, className }) => {
	const baseId = React.useId();
	const inputId = `${baseId}-input`;
	const inputRef = React.useRef<HTMLInputElement>(null);
	const dialogRef = React.useRef<HTMLDialogElement>(null);
	const modal = useModal(dialogRef, { transitionMs: 0, onOpen: () => inputRef.current?.focus() });
	const search = useHeaderSearch(modal.close);
	const scopeLabel = findSearchScope(search.resolution.scope).label.toLowerCase();

	return (
		<>
			<button
				ref={triggerRef}
				type="button"
				className={classNames(styles.iconButton, className)}
				aria-label="Search"
				aria-haspopup="dialog"
				aria-expanded={modal.isOpen}
				aria-controls={modal.id}
				onClick={modal.open}
			>
				<Icon name="searchSolid" size="sm" />
			</button>

			<Drawer modal={modal} label="Search">
				<form
					role="search"
					className={styles.drawerForm}
					autoComplete="off"
					onSubmit={(event) => {
						event.preventDefault();
						search.run();
					}}
				>
					<div className={styles.drawerTop}>
						<label htmlFor={inputId} className={styles.visuallyHidden}>
							Search JPLearning
						</label>
						<div className={classNames(styles.field, styles.drawerField)}>
							<Icon name="searchSolid" size="sm" className={styles.fieldIcon} />
							<input
								ref={inputRef}
								id={inputId}
								type="search"
								className={styles.input}
								placeholder={SEARCH_PLACEHOLDER}
								enterKeyHint="search"
								value={search.query}
								onChange={(event) => search.setQuery(event.target.value)}
								onKeyDown={(event) => {
									// A search input clears itself on Escape; close the drawer instead.
									if (event.key === 'Escape') {
										event.preventDefault();
										modal.close();
									}
								}}
							/>
						</div>
						<button type="button" className={styles.cancel} onClick={modal.close}>
							Cancel
						</button>
					</div>

					<div className={styles.drawerBody}>
						<SearchBody search={search} baseId={baseId} mode="drawer" />
					</div>

					<div className={styles.drawerFoot}>
						<Button type="submit" variant="primary" isFullWidth className={styles.drawerSubmit}>
							{search.hasQuery ? 'Search' : 'Browse'} {scopeLabel}
						</Button>
					</div>
				</form>
			</Drawer>
		</>
	);
};
