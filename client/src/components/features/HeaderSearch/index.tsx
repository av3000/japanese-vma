import * as React from 'react';
import styles from './HeaderSearch.module.css';
import { HeaderSearchDrawer } from './HeaderSearchDrawer';
import { HeaderSearchPanel } from './HeaderSearchPanel';

export { HeaderSearchDrawer } from './HeaderSearchDrawer';
export { HeaderSearchPanel } from './HeaderSearchPanel';
export { DEFAULT_SEARCH_SCOPE, SEARCH_SCOPES, scopedSearchUrl, type SearchScope } from './searchScopes';

/** Must match the `min-width: 1024px` breakpoint in HeaderSearch.module.css and Header.module.css. */
const DESKTOP_MEDIA = '(min-width: 1024px)';

const isDesktop = () => typeof window.matchMedia !== 'function' || window.matchMedia(DESKTOP_MEDIA).matches;

const isTypingTarget = (target: EventTarget | null) =>
	target instanceof HTMLElement &&
	(target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

/**
 * The Header search on every page: the inline panel from 1024px, the icon button and drawer below.
 * Both render and CSS shows one, so each sits where the Header bar needs it. "/" (outside a text
 * field) focuses the panel input on desktop and opens the drawer on mobile.
 */
export const HeaderSearch: React.FC = () => {
	const inputRef = React.useRef<HTMLInputElement>(null);
	const triggerRef = React.useRef<HTMLButtonElement>(null);

	React.useEffect(() => {
		const handleKeyDown = (event: KeyboardEvent) => {
			if (event.key !== '/' || event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) return;
			if (isTypingTarget(event.target)) return;
			event.preventDefault();
			if (isDesktop()) {
				inputRef.current?.focus();
			} else {
				// Focus first so the drawer returns focus to its trigger when it closes.
				triggerRef.current?.focus();
				triggerRef.current?.click();
			}
		};
		document.addEventListener('keydown', handleKeyDown);
		return () => document.removeEventListener('keydown', handleKeyDown);
	}, []);

	return (
		<>
			<HeaderSearchPanel inputRef={inputRef} className={styles.desktopOnly} />
			<HeaderSearchDrawer triggerRef={triggerRef} className={styles.mobileOnly} />
		</>
	);
};
