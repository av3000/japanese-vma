/**
 * Site navigation, the single source for the Header groups and the Footer columns. Keep the
 * order stable: the Header disclosures, the mobile drawer and the Footer all render it as-is.
 */
export interface NavItem {
	to: string;
	label: string;
	/** Shown under the label in the desktop Header disclosure only. */
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
