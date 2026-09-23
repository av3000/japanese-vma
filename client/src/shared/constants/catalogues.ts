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

export const CATALOGUE_TYPE_OPTIONS = Object.entries(CATALOGUE_TYPE_LABELS).map(([value, label]) => ({
	value: Number(value) as CustomCatalogueType,
	label,
}));

export const CATALOGUE_ROUTES = {
	list: '/catalogues',
	detail: (catalogueId: string) => `/catalogues/${catalogueId}`,
	create: '/catalogues/new',
	edit: (catalogueId: string) => `/catalogues/${catalogueId}/edit`,
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

export const isCustomCatalogueType = (value: number): value is CustomCatalogueType => {
	return Object.prototype.hasOwnProperty.call(CATALOGUE_TYPE_LABELS, value);
};

export const resolveCatalogueTypeLabel = (value: number) => {
	return isCustomCatalogueType(value) ? CATALOGUE_TYPE_LABELS[value] : 'Unknown';
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
