import classNames from 'classnames';
import styles from './Header.module.css';

export interface NavItem {
	to: string;
	label: string;
	/** Shown under the label in the desktop disclosure only. */
	description?: string;
}

export const EXPLORE_LINKS: readonly NavItem[] = [
	{ to: '/articles', label: 'Articles', description: 'Graded reading from the news' },
	{ to: '/catalogues', label: 'Catalogues', description: 'Collections by the community' },
	{ to: '/community', label: 'Community', description: 'Questions and discussion' },
];

export const DICTIONARY_LINKS: readonly NavItem[] = [
	{ to: '/radicals', label: 'Radicals' },
	{ to: '/kanjis', label: 'Kanji' },
	{ to: '/words', label: 'Words' },
	{ to: '/sentences', label: 'Sentences' },
];

export const NAV_SECTIONS = [
	{ id: 'explore', label: 'Explore', links: EXPLORE_LINKS },
	{ id: 'dictionary', label: 'Dictionary', links: DICTIONARY_LINKS },
] as const;

export const navLinkClass = ({ isActive }: { isActive: boolean }) =>
	classNames(styles.link, isActive && styles.linkActive);
