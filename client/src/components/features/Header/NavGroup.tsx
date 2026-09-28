import * as React from 'react';
import { useLocation } from 'react-router-dom';
import classNames from 'classnames';
import { Icon } from '@/components/shared/Icon';
import { useOnClickAway } from '@/hooks/useOnClickAway';
import styles from './Header.module.css';

interface NavGroupProps {
	/** Visible button content; becomes the button's accessible name. */
	label: React.ReactNode;
	/** DOM id for the disclosed list; wired to `aria-controls`. */
	id: string;
	/** `<li>` children. */
	children: React.ReactNode;
	/** Extra class on the wrapping `<li>`, e.g. to lay the group out flat at some widths. */
	className?: string;
}

/**
 * A group of navigation links behind a disclosure button.
 *
 * Deliberately not an ARIA menu: these are ordinary links, so the pattern is a
 * button with `aria-expanded` controlling a list. Closes on Escape (returning
 * focus to the button), on outside click, and on route change.
 */
export const NavGroup: React.FC<NavGroupProps> = ({ label, id, children, className }) => {
	const [isOpen, setIsOpen] = React.useState(false);
	const wrapperRef = React.useRef<HTMLLIElement>(null);
	const buttonRef = React.useRef<HTMLButtonElement>(null);
	const { pathname } = useLocation();

	React.useEffect(() => {
		setIsOpen(false);
	}, [pathname]);

	const close = React.useCallback(() => setIsOpen(false), []);
	useOnClickAway(wrapperRef, close, isOpen);

	// Escape closes the group from anywhere inside it and returns focus to the button.
	React.useEffect(() => {
		if (!isOpen) return;
		const handleKeyDown = (event: KeyboardEvent) => {
			if (event.key !== 'Escape') return;
			const target = event.target as Node | null;
			if (target && wrapperRef.current?.contains(target)) {
				event.stopPropagation();
				setIsOpen(false);
				buttonRef.current?.focus();
			}
		};
		document.addEventListener('keydown', handleKeyDown);
		return () => document.removeEventListener('keydown', handleKeyDown);
	}, [isOpen]);

	return (
		<li ref={wrapperRef} className={classNames(styles.group, className)}>
			<button
				ref={buttonRef}
				type="button"
				className={classNames(styles.link, styles.groupButton)}
				aria-expanded={isOpen}
				aria-controls={id}
				onClick={() => setIsOpen((open) => !open)}
			>
				{label}
				<Icon name="chevron" size="sm" className={styles.chevron} />
			</button>
			<ul id={id} className={styles.groupList} hidden={!isOpen}>
				{children}
			</ul>
		</li>
	);
};
