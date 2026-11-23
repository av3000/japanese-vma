export const SavedListType = {
	KNOWNRADICALS: 1,
	KNOWNKANJIS: 2,
	KNOWNWORDS: 3,
	KNOWNSENTENCES: 4,
	RADICALS: 5,
	KANJIS: 6,
	WORDS: 7,
	SENTENCES: 8,
	ARTICLES: 9,
	LYRICS: 10,
	ARTISTS: 11,
} as const;

export type SavedListType = (typeof SavedListType)[keyof typeof SavedListType];

/**
 * Usage: `SavedListTypeLabel[SavedListType.ARTICLES];`
 */
export const SavedListTypeLabel = {
	[SavedListType.KNOWNRADICALS]: 'Known Radicals',
	[SavedListType.KNOWNKANJIS]: 'Known Kanji',
	[SavedListType.KNOWNWORDS]: 'Known Words',
	[SavedListType.KNOWNSENTENCES]: 'Known Sentences',
	[SavedListType.RADICALS]: 'Radicals',
	[SavedListType.KANJIS]: 'Kanji',
	[SavedListType.WORDS]: 'Words',
	[SavedListType.SENTENCES]: 'Sentences',
	[SavedListType.ARTICLES]: 'Articles',
	[SavedListType.LYRICS]: 'Lyrics',
	[SavedListType.ARTISTS]: 'Artists',
} satisfies Record<SavedListType, string>;

/**
 * Usage: `SavedListTypeUuid[SavedListType.ARTICLES];`
 */
export const SavedListTypeUuid = {
	[SavedListType.KNOWNRADICALS]: '0eea67ac-2676-4b68-947f-391eab2e1416',
	[SavedListType.KNOWNKANJIS]: '1c3f3b1e-2dcb-4f0c-8f7a-2e5e4f3b6c9a',
	[SavedListType.KNOWNWORDS]: '2a4b5c6d-3e7f-4a8b-9c0d-1e2f3a4b5c6d',
	[SavedListType.KNOWNSENTENCES]: '3b4c5d6e-4f7a-5b8c-9d0e-1f2a3b4c5d6e',
	[SavedListType.RADICALS]: '4c5d6e7f-5a8b-6c9d-0e1f-2a3b4c5d6e7f',
	[SavedListType.KANJIS]: '5d6e7f8a-6b9c-7d0e-1f2a-3b4c5d6e7f8a',
	[SavedListType.WORDS]: '6e7f8a9b-7c0d-8e1f-2a3b-4c5d6e7f8a9b',
	[SavedListType.SENTENCES]: '7f8a9b0c-8d1e-9f2a-3b4c-5d6e7f8a9b0c',
	[SavedListType.ARTICLES]: '8a9b0c1d-9e2f-0a3b-4c5d-6e7f8a9b0c1d',
	[SavedListType.LYRICS]: '9b0c1d2e-0f3a-1b4c-5d6e-7f8a9b0c1d2e',
	[SavedListType.ARTISTS]: '0c1d2e3f-1a4b-2c5d-6e7f-8a9b0c1d2e3f',
} satisfies Record<SavedListType, string>;
