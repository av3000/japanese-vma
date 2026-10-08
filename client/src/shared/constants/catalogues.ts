import { SavedListType } from '@/shared/constants/enums';

export const CATALOGUE_TYPE_LABELS = {
	5: 'Radicals',
	6: 'Kanjis',
	7: 'Words',
	8: 'Sentences',
	9: 'Articles',
} as const;

export type CustomCatalogueType = keyof typeof CATALOGUE_TYPE_LABELS;
export type CataloguePdfExportKind = 'kanji' | 'words' | 'radicals' | 'sentences';

// Each group pairs a SavedListType with its "known" variant, mirroring
// CataloguePdfExportService::supportsCatalogueType on the backend: a type the client offers
// the download for but the backend rejects would surface as a 422 the user cannot act on.
const CATALOGUE_PDF_TYPES: ReadonlyArray<readonly [CataloguePdfExportKind, readonly number[]]> = [
	['kanji', [2, 6]],
	['words', [3, 7]],
	['radicals', [1, 5]],
	['sentences', [4, 8]],
];

/** What a catalogue holds, with each "known" type folded into the family it saves. */
export type CatalogueFamily = 'radicals' | 'kanji' | 'words' | 'sentences' | 'articles';

const CATALOGUE_FAMILIES: ReadonlyArray<readonly [CatalogueFamily, readonly number[]]> = [
	['radicals', [1, 5]],
	['kanji', [2, 6]],
	['words', [3, 7]],
	['sentences', [4, 8]],
	['articles', [9]],
];

/**
 * The families flashcards can be built from (epic #413). Sentences have no translation
 * column and articles are not cards, so a Study action for them would only surface a 422.
 * Mirrors FlashcardQuestion::baseType on the backend.
 */
export type StudyFamily = Extract<CatalogueFamily, 'kanji' | 'words' | 'radicals'>;

const STUDY_FAMILIES: ReadonlySet<CatalogueFamily> = new Set<CatalogueFamily>(['kanji', 'words', 'radicals']);

export const studyFamilyFor = (catalogueType: number): StudyFamily | null => {
	const family = resolveCatalogueFamily(catalogueType);
	return family !== null && STUDY_FAMILIES.has(family) ? (family as StudyFamily) : null;
};

export const isCatalogueStudySupported = (value: number) => studyFamilyFor(value) !== null;

const BOOKMARK_TYPE_BY_FAMILY: Record<StudyFamily, SavedListType> = {
	kanji: SavedListType.KANJIS,
	words: SavedListType.WORDS,
	radicals: SavedListType.RADICALS,
};

/** The custom catalogue type a studied item can be saved into from the summary. */
export const studyBookmarkTypeFor = (catalogueType: number): SavedListType | null => {
	const family = studyFamilyFor(catalogueType);
	return family ? BOOKMARK_TYPE_BY_FAMILY[family] : null;
};

export const CATALOGUE_TYPE_OPTIONS = Object.entries(CATALOGUE_TYPE_LABELS).map(([value, label]) => ({
	value: Number(value) as CustomCatalogueType,
	label,
}));

/** The type filter's "All" choice. Not a catalogue type, so it sends no `type` param. */
export const CATALOGUE_TYPE_FILTER_ALL = '';

/** Options for a catalogue type filter: "All", then every custom type as a string select value. */
export const CATALOGUE_TYPE_FILTER_OPTIONS: ReadonlyArray<{ value: string; label: string }> = [
	{ value: CATALOGUE_TYPE_FILTER_ALL, label: 'All' },
	...CATALOGUE_TYPE_OPTIONS.map(({ value, label }) => ({ value: String(value), label })),
];

export const CATALOGUE_ROUTES = {
	list: '/catalogues',
	detail: (catalogueId: string) => `/catalogues/${catalogueId}`,
	create: '/catalogues/new',
	edit: (catalogueId: string) => `/catalogues/${catalogueId}/edit`,
	study: (catalogueId: string) => `/catalogues/${catalogueId}/study`,
	legacyList: '/lists',
	legacyDetail: (catalogueId: string) => `/list/${catalogueId}`,
	legacyCreate: '/newlist',
	legacyEdit: (catalogueId: string) => `/list/edit/${catalogueId}`,
} as const;

const CATALOGUE_UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

// Mirrors the `[1-9][0-9]{0,17}` route constraint on `GET /catalogues/legacy/{id}`
// (processor-api/routes/api_v1.php), so an id the backend would answer with a bare
// 404 never leaves the client. `Number.isSafeInteger` covers the tail of that range
// the pattern allows but JavaScript cannot represent without rounding.
const CATALOGUE_LEGACY_ID_PATTERN = /^[1-9][0-9]{0,17}$/;

/**
 * The four Known lists every user gets at registration (`CatalogueType` 1–4 on the backend).
 * Known marking depends on them and nothing recreates a deleted one, so the client offers no
 * edit or delete for them (UI-DASH-04, #454; backend guard in #459).
 */
const BUILT_IN_CATALOGUE_TYPES: ReadonlySet<number> = new Set([1, 2, 3, 4]);

export const isBuiltInCatalogueType = (value: number): boolean => BUILT_IN_CATALOGUE_TYPES.has(value);

export const isCustomCatalogueType = (value: number): value is CustomCatalogueType => {
	return Object.prototype.hasOwnProperty.call(CATALOGUE_TYPE_LABELS, value);
};

export const resolveCatalogueTypeLabel = (value: number) => {
	return isCustomCatalogueType(value) ? CATALOGUE_TYPE_LABELS[value] : 'Unknown';
};

/** `null` for types that hold neither (lyrics, artists) or are unknown. */
export const resolveCatalogueFamily = (value: number): CatalogueFamily | null => {
	return CATALOGUE_FAMILIES.find(([, types]) => types.includes(value))?.[0] ?? null;
};

export const resolveCataloguePdfExportKind = (value: number): CataloguePdfExportKind | null => {
	return CATALOGUE_PDF_TYPES.find(([, types]) => types.includes(value))?.[0] ?? null;
};

export const isCataloguePdfExportSupported = (value: number) => resolveCataloguePdfExportKind(value) !== null;

export const isCatalogueRouteUuid = (value: string) => {
	return CATALOGUE_UUID_PATTERN.test(value);
};

export const parseCatalogueLegacyId = (value: string | undefined): number | null => {
	if (!value || !CATALOGUE_LEGACY_ID_PATTERN.test(value)) {
		return null;
	}

	const legacyId = Number(value);

	return Number.isSafeInteger(legacyId) ? legacyId : null;
};
